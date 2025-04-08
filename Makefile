# Makefile for building a universal (fat) binary for macOS (x86_64 and arm64)

TARGET = ExtractApp
SOURCES = main.m

# List the architectures you want to support.
ARCHS = x86_64 arm64

# For each architecture, add a -arch flag.
CFLAGS = -fobjc-arc $(patsubst %,-arch %,$(ARCHS))
FRAMEWORKS = -framework Foundation

all: $(TARGET)

$(TARGET): $(SOURCES)
	clang $(CFLAGS) -o $(TARGET) $(SOURCES) $(FRAMEWORKS)

clean:
	rm -f $(TARGET)

.PHONY: all clean

