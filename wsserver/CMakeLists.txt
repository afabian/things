cmake_minimum_required(VERSION 3.16)
project(ThingsWs VERSION 1.0)

set(CMAKE_CXX_STANDARD 17)
set(CMAKE_AUTOMOC ON)

find_package(Qt6 REQUIRED COMPONENTS Core Network WebSockets)

add_executable(things-ws
    main.cpp
    StateServer.cpp
)

target_link_libraries(things-ws PRIVATE
    Qt6::Core
    Qt6::Network
    Qt6::WebSockets
)

install(TARGETS things-ws DESTINATION bin)
