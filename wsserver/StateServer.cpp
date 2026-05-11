#include "StateServer.h"
#include <QWebSocket>
#include <QUdpSocket>
#include <QNetworkReply>
#include <QNetworkRequest>
#include <QUrl>

StateServer::StateServer(const QString &apiBase, quint16 wsPort,
                         quint16 notifyPort, QObject *parent)
    : QObject(parent)
    , m_server("things-ws", QWebSocketServer::NonSecureMode, this)
    , m_apiBase(apiBase)
{
    // WebSocket listener
    if (!m_server.listen(QHostAddress::LocalHost, wsPort)) {
        qCritical() << "Listen failed on port" << wsPort << ":" << m_server.errorString();
        return;
    }

    connect(&m_server, &QWebSocketServer::newConnection,
            this, &StateServer::onNewConnection);

    qInfo() << "things-ws listening on port" << wsPort;

    // UDP notification socket for PHP push — localhost only
    if (m_notifySocket.bind(QHostAddress::LocalHost, notifyPort)) {
        connect(&m_notifySocket, &QUdpSocket::readyRead,
                this, &StateServer::onNotified);
        qInfo() << "Notify UDP port:" << notifyPort;
    } else {
        qWarning() << "Could not bind notify port:" << notifyPort
                   << "— falling back to poll only";
    }

    // Fallback poll: catches anything PHP missed (e.g. direct DB edits)
    m_fallbackTimer.setInterval(10000);
    connect(&m_fallbackTimer, &QTimer::timeout, this, &StateServer::poll);
    m_fallbackTimer.start();

    // Fetch initial state so new clients get it immediately on connect
    fetchAndBroadcast();
}

bool StateServer::isListening() const
{
    return m_server.isListening();
}

// --- PHP push notification ---

void StateServer::onNotified()
{
    // Drain all pending datagrams — content irrelevant, arrival is the signal.
    while (m_notifySocket.hasPendingDatagrams())
        m_notifySocket.readDatagram(nullptr, 0);

    fetchAndBroadcast();
}

// --- Fallback poll ---

void StateServer::poll()
{
    fetchAndBroadcast();
}

// --- Fetch & broadcast ---

void StateServer::fetchAndBroadcast()
{
    if (m_fetchPending) return;

    m_fetchPending = true;

    auto *reply = m_nam.get(QNetworkRequest(QUrl(m_apiBase + "/state.get")));

    connect(reply, &QNetworkReply::finished, this, [this, reply]() {
        m_fetchPending = false;
        reply->deleteLater();

        if (reply->error() != QNetworkReply::NoError) return;

        QByteArray state = reply->readAll().trimmed();

        if (state == m_lastState) return;

        m_lastState = state;
        broadcast(state);
    });
}

// --- WebSocket clients ---

void StateServer::onNewConnection()
{
    auto *ws = m_server.nextPendingConnection();

    connect(ws, &QWebSocket::disconnected, this, &StateServer::onClientDisconnected);

    m_clients.append(ws);

    qInfo() << "Client connected —" << m_clients.size() << "total";

    if (!m_lastState.isEmpty())
        ws->sendTextMessage(QString::fromUtf8(m_lastState));
}

void StateServer::onClientDisconnected()
{
    auto *ws = qobject_cast<QWebSocket *>(sender());

    m_clients.removeAll(ws);
    ws->deleteLater();

    qInfo() << "Client disconnected —" << m_clients.size() << "total";
}

void StateServer::broadcast(const QByteArray &msg)
{
    QString text = QString::fromUtf8(msg);

    for (auto *ws : m_clients)
        ws->sendTextMessage(text);
}
