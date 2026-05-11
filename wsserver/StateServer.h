#pragma once
#include <QObject>
#include <QWebSocketServer>
#include <QUdpSocket>
#include <QNetworkAccessManager>
#include <QTimer>
#include <QList>
#include <QByteArray>

class QWebSocket;

class StateServer : public QObject
{
    Q_OBJECT
public:
    explicit StateServer(const QString &apiBase, quint16 wsPort,
                         quint16 notifyPort, QObject *parent = nullptr);

    bool isListening() const;

private slots:
    void onNewConnection();
    void onClientDisconnected();
    void onNotified();
    void poll();

private:
    void fetchAndBroadcast();
    void broadcast(const QByteArray &msg);

    QWebSocketServer      m_server;
    QUdpSocket            m_notifySocket;
    QNetworkAccessManager m_nam;
    QList<QWebSocket *>   m_clients;
    QTimer                m_fallbackTimer;

    QString    m_apiBase;
    QByteArray m_lastState;
    bool       m_fetchPending = false;
};
