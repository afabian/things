BUILD := build

all: $(BUILD)/things-viewer

$(BUILD)/things-viewer: $(BUILD)/Makefile $(wildcard *.cpp *.h CMakeLists.txt)
	cmake --build $(BUILD)

$(BUILD)/Makefile:
	cmake -B $(BUILD) -DCMAKE_BUILD_TYPE=Release

run: $(BUILD)/things-viewer
	$(BUILD)/things-viewer

clean:
	rm -rf $(BUILD)

.PHONY: all run clean
