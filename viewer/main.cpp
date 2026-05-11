#include <QApplication>
#include <QIcon>
#include <QSystemTrayIcon>
#include "MainWindow.h"

int main(int argc, char *argv[])
{
    QApplication app(argc, argv);
    app.setOrganizationName("things");
    app.setApplicationName("things-viewer");
    app.setWindowIcon(QIcon(":/cardboard-box.svg"));
    app.setQuitOnLastWindowClosed(false);

    if (!QSystemTrayIcon::isSystemTrayAvailable()) {
        qCritical("System tray not available — install an appindicator extension");
        return 1;
    }

    MainWindow w;
    return app.exec();
}
