#include <QCoreApplication>
#include <QCommandLineParser>
#include "StateServer.h"

int main(int argc, char *argv[])
{
    QCoreApplication app(argc, argv);
    app.setOrganizationName("things");
    app.setApplicationName("things-ws");

    QCommandLineParser parser;
    parser.setApplicationDescription("Things WebSocket state server");
    parser.addHelpOption();

    QCommandLineOption wsPortOpt({"p", "port"},   "WebSocket port (default 8765)",       "port", "8765");
    QCommandLineOption notifyOpt({"n", "notify"}, "PHP notify TCP port (default 8766)",  "port", "8766");
    QCommandLineOption apiOpt(   {"a", "api"},    "API base URL",
                                 "url", "http://localhost/things");

    parser.addOption(wsPortOpt);
    parser.addOption(notifyOpt);
    parser.addOption(apiOpt);
    parser.process(app);

    quint16 wsPort     = static_cast<quint16>(parser.value(wsPortOpt).toUShort());
    quint16 notifyPort = static_cast<quint16>(parser.value(notifyOpt).toUShort());
    QString apiBase    = parser.value(apiOpt);

    StateServer server(apiBase, wsPort, notifyPort);

    if (!server.isListening())
        return 1;

    return app.exec();
}
