cmake_minimum_required(VERSION 3.16)
project(ThingsViewer VERSION 1.0)

set(CMAKE_CXX_STANDARD 17)
set(CMAKE_AUTOMOC ON)
set(CMAKE_AUTORCC ON)

find_package(Qt6 REQUIRED COMPONENTS Core Widgets Network DBus Pdf PdfWidgets Svg WebSockets)

add_executable(things-viewer
    main.cpp
    MainWindow.cpp
    ApiPoller.cpp
    ScannerInput.cpp
    DocViewer.cpp
    ItemPanel.cpp
    ErrorOverlay.cpp
    SettingsDialog.cpp
    resources.qrc
)

target_link_libraries(things-viewer PRIVATE
    Qt6::Core
    Qt6::Widgets
    Qt6::Network
    Qt6::DBus
    Qt6::Pdf
    Qt6::PdfWidgets
    Qt6::Svg
    Qt6::WebSockets
)


install(TARGETS things-viewer DESTINATION bin)
