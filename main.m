#import <Foundation/Foundation.h>

// Helper function: Convert a hexadecimal string to an NSData object.
NSData *dataFromHexString(NSString *hexString) {
    NSMutableData *data = [NSMutableData data];
    NSUInteger length = [hexString length];
    for (NSUInteger i = 0; i < length; i += 2) {
        if (i + 2 > length)
            break;
        NSString *byteString = [hexString substringWithRange:NSMakeRange(i, 2)];
        unsigned int byte;
        [[NSScanner scannerWithString:byteString] scanHexInt:&byte];
        uint8_t b = (uint8_t)byte;
        [data appendBytes:&b length:1];
    }
    return data;
}

int main(int argc, const char * argv[]) {
    @autoreleasepool {
        // Check for exactly one command-line argument.
        if (argc != 2) {
            printf("Usage: %s <path_to_tarball>\n", argv[0]);
            return 1;
        }
        
        // Get the tarball path argument.
        NSString *tarPath = [NSString stringWithUTF8String:argv[1]];
        NSFileManager *fileManager = [NSFileManager defaultManager];
        if (![fileManager fileExistsAtPath:tarPath]) {
            fprintf(stderr, "❌ Error: File '%s' not found.\n", argv[1]);
            return 1;
        }
        
        // Check if the file has a valid extension (.tar.gz or .tgz).
        NSString *fileName = [tarPath lastPathComponent];
        if (!([fileName hasSuffix:@".tar.gz"] || [fileName hasSuffix:@".tgz"])) {
            fprintf(stderr, "❌ Error: The file must be a .tar.gz or .tgz archive.\n");
            return 1;
        }
        
        // Derive the extraction directory name.
        // For a file like "foo.tar.gz" or "foo.tgz" we want to remove the compression extensions.
        NSString *extractedDirName = nil;
        if ([fileName hasSuffix:@".tar.gz"]) {
            extractedDirName = [fileName substringToIndex:[fileName length] - [@".tar.gz" length]];
        } else if ([fileName hasSuffix:@".tgz"]) {
            extractedDirName = [fileName substringToIndex:[fileName length] - [@".tgz" length]];
        }
        NSString *currentDir = [fileManager currentDirectoryPath];
        NSString *extractedDir = [currentDir stringByAppendingPathComponent:extractedDirName];
        
        // If the extraction directory exists, remove it to start fresh.
        if ([fileManager fileExistsAtPath:extractedDir]) {
            NSError *removeError = nil;
            [fileManager removeItemAtPath:extractedDir error:&removeError];
            if (removeError) {
                fprintf(stderr, "❌ Error removing existing directory: %s\n",
                        [[removeError localizedDescription] UTF8String]);
                return 1;
            }
        }
        
        // Create the extraction directory.
        NSError *createError = nil;
        [fileManager createDirectoryAtPath:extractedDir
               withIntermediateDirectories:YES
                                attributes:nil
                                     error:&createError];
        if (createError) {
            fprintf(stderr, "❌ Error creating extraction directory: %s\n",
                    [[createError localizedDescription] UTF8String]);
            return 1;
        }
        
        // Use NSTask to call the system tar program to extract the archive.
        // The command used is: tar -xzf <tarPath> -C <extractedDir>
        NSTask *task = [[NSTask alloc] init];
        [task setLaunchPath:@"/usr/bin/tar"];
        [task setArguments:@[@"-xzf", tarPath, @"-C", extractedDir]];
        [task launch];
        [task waitUntilExit];
        int terminationStatus = [task terminationStatus];
        if (terminationStatus != 0) {
            fprintf(stderr, "❌ Error extracting tarball. Tar process exited with status %d.\n",
                    terminationStatus);
            // Clean up the extraction directory.
            [fileManager removeItemAtPath:extractedDir error:nil];
            return 1;
        }
        
        // Search through the extracted directory to find IODeviceTree.txt.
        NSString *iodeviceTreePath = nil;
        NSDirectoryEnumerator *enumerator = [fileManager enumeratorAtPath:extractedDir];
        NSString *relativePath;
        while ((relativePath = [enumerator nextObject])) {
            if ([[relativePath lastPathComponent] isEqualToString:@"IODeviceTree.txt"]) {
                iodeviceTreePath = [extractedDir stringByAppendingPathComponent:relativePath];
                break;
            }
        }
        
        if (iodeviceTreePath == nil) {
            fprintf(stderr, "❌ Error: 'IODeviceTree.txt' not found in the extracted files.\n");
            [fileManager removeItemAtPath:extractedDir error:nil];
            return 1;
        }
        
        // Load the contents of IODeviceTree.txt into a string.
        NSError *readError = nil;
        NSString *content = [NSString stringWithContentsOfFile:iodeviceTreePath
                                                      encoding:NSUTF8StringEncoding
                                                         error:&readError];
        if (readError) {
            fprintf(stderr, "❌ Error reading file: %s\n",
                    [[readError localizedDescription] UTF8String]);
            [fileManager removeItemAtPath:extractedDir error:nil];
            return 1;
        }
        
        // Default values for extracted information.
        NSString *model = @"N/A";
        NSString *serialNumber = @"N/A";
        NSString *firstName = @"N/A";
        NSString *lastName = @"N/A";
        NSString *appleID = @"N/A";
        
        NSError *regexError = nil;
        
        // --- Parse for the model ---
        // Pattern:  model"\s*=\s*<"([^"]+)"
        NSString *patternModel = @"model\\\"\\s*=\\s*<\\\"([^\\\"]+)\\\"";
        NSRegularExpression *regexModel = [NSRegularExpression regularExpressionWithPattern:patternModel
                                                                                    options:0
                                                                                      error:&regexError];
        if (regexError) {
            fprintf(stderr, "❌ Error creating regex for model: %s\n",
                    [[regexError localizedDescription] UTF8String]);
        } else {
            NSTextCheckingResult *match = [regexModel firstMatchInString:content options:0
                                                                    range:NSMakeRange(0, [content length])];
            if (match && [match numberOfRanges] > 1) {
                model = [content substringWithRange:[match rangeAtIndex:1]];
            }
        }
        
        // --- Parse for the serial number ---
        // Pattern:  IOPlatformSerialNumber"\s*=\s*"([^"]+)"
        NSString *patternSerial = @"IOPlatformSerialNumber\\\"\\s*=\\s*\\\"([^\\\"]+)\\\"";
        NSRegularExpression *regexSerial = [NSRegularExpression regularExpressionWithPattern:patternSerial
                                                                                     options:0
                                                                                       error:&regexError];
        if (regexError) {
            fprintf(stderr, "❌ Error creating regex for serial number: %s\n",
                    [[regexError localizedDescription] UTF8String]);
        } else {
            NSTextCheckingResult *match = [regexSerial firstMatchInString:content options:0
                                                                       range:NSMakeRange(0, [content length])];
            if (match && [match numberOfRanges] > 1) {
                serialNumber = [content substringWithRange:[match rangeAtIndex:1]];
            }
        }
        
        // --- Parse for the 'fmm-mobileme-token-FMM' key and its binary data ---
        // Pattern:  "fmm-mobileme-token-FMM"\s+=\s+<([0-9a-fA-F]+)>
        NSString *patternToken = @"\\\"fmm-mobileme-token-FMM\\\"\\s+=\\s+<([0-9a-fA-F]+)>";
        NSRegularExpression *regexToken = [NSRegularExpression regularExpressionWithPattern:patternToken
                                                                                    options:0
                                                                                      error:&regexError];
        if (regexError) {
            fprintf(stderr, "❌ Error creating regex for token: %s\n",
                    [[regexError localizedDescription] UTF8String]);
        } else {
            NSTextCheckingResult *match = [regexToken firstMatchInString:content options:0
                                                                    range:NSMakeRange(0, [content length])];
            if (match && [match numberOfRanges] > 1) {
                NSString *hexData = [content substringWithRange:[match rangeAtIndex:1]];
                NSData *binaryData = dataFromHexString(hexData);
                NSError *plistError = nil;
                id plistObject = [NSPropertyListSerialization propertyListWithData:binaryData
                                                                           options:NSPropertyListImmutable
                                                                            format:NULL
                                                                             error:&plistError];
                if (plistError) {
                    fprintf(stderr, "❌ Error parsing plist data: %s\n",
                            [[plistError localizedDescription] UTF8String]);
                } else if ([plistObject isKindOfClass:[NSDictionary class]]) {
                    NSDictionary *plistDict = (NSDictionary *)plistObject;
                    NSDictionary *userInfo = plistDict[@"userInfo"];
                    if ([userInfo isKindOfClass:[NSDictionary class]]) {
                        if (userInfo[@"InUseOwnerFirstName"]) {
                            firstName = userInfo[@"InUseOwnerFirstName"];
                        }
                        if (userInfo[@"InUseOwnerLastName"]) {
                            lastName = userInfo[@"InUseOwnerLastName"];
                        }
                    }
                    if (plistDict[@"username"]) {
                        appleID = plistDict[@"username"];
                    }
                }
            }
        }
        
        // --- Print the output ---
        printf("\n🔍 **Extracted Information:**\n");
        printf("📌 **Model:** %s\n", [model UTF8String]);
        printf("🔢 **Serial Number:** %s\n", [serialNumber UTF8String]);
        printf("👤 **First Name:** %s\n", [firstName UTF8String]);
        printf("👤 **Last Name:** %s\n", [lastName UTF8String]);
        printf("📧 **Apple ID:** %s\n\n", [appleID UTF8String]);
        
        // Clean up: remove the extracted directory.
        [fileManager removeItemAtPath:extractedDir error:nil];
    }
    return 0;
}

