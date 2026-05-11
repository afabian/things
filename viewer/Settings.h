#pragma once
#include <QSettings>
#include <QString>

struct Settings {
    static QString serverUrl() {
        return QSettings().value("server_url", "http://10.0.0.10/things").toString();
    }
    static void setServerUrl(const QString &v) { QSettings().setValue("server_url", v); }

    static QString wsUrl() {
        QString url = serverUrl();
        if (url.startsWith("https://"))
            return "wss://" + url.mid(8) + "/ws";
        return "ws://" + url.mid(7) + "/ws";
    }

    static QString scannerDevice() {
        return QSettings().value("scanner_device", "").toString();
    }
    static void setScannerDevice(const QString &v) { QSettings().setValue("scanner_device", v); }
};
