#pragma once
#include <QObject>
#include <QWebSocket>
#include <QNetworkAccessManager>
#include <QTimer>
#include <QStringList>

struct ViewerItem {
    int id = 0;
    QString name;
    QString partNumber;
    int quantity = 0;
    QStringList locationPath;
};

struct ViewerRef {
    int id = 0;
    QString name;
    QString fileType; // "pdf", "image", "md"
    QString url;
};

class ApiPoller : public QObject
{
    Q_OBJECT
public:
    explicit ApiPoller(QObject *parent = nullptr);
    void start();

public slots:
    void postScan(const QString &qrSerial);

signals:
    void stateChanged(bool hasItem, ViewerItem item, bool hasRef, ViewerRef ref);
    void serverReachabilityChanged(bool isUp);

private slots:
    void onWsConnected();
    void onWsDisconnected();
    void onWsMessage(const QString &msg);
    void reconnect();
    void reportServerDown();

private:
    void fetchViewerData();

    QWebSocket            m_ws;
    QNetworkAccessManager m_nam;
    QTimer                m_reconnectTimer;
    QTimer                m_serverDownTimer;

    int     m_lastItemId    = -1;
    QString m_lastUpdatedAt;
    bool    m_initialized   = false;
    bool    m_serverUp      = true;
    bool    m_everConnected = false;
    bool    m_fetchPending  = false;
};
