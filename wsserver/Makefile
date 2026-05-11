BUILD := build

all: $(BUILD)/things-ws

$(BUILD)/things-ws: $(BUILD)/Makefile $(wildcard *.cpp *.h CMakeLists.txt)
	cmake --build $(BUILD)

$(BUILD)/Makefile:
	cmake -B $(BUILD) -DCMAKE_BUILD_TYPE=Release

clean:
	rm -rf $(BUILD)

.PHONY: all clean
