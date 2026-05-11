#include "ApiPoller.h"
#include "Settings.h"
#include <QNetworkReply>
#include <QNetworkRequest>
#include <QJsonDocument>
#include <QJsonObject>
#include <QJsonArray>
#include <QUrl>

ApiPoller::ApiPoller(QObject *parent) : QObject(parent)
{
}

void ApiPoller::start()
{
    m_reconnectTimer.setSingleShot(true);
    m_reconnectTimer.setInterval(3000);
    connect(&m_reconnectTimer, &QTimer::timeout, this, &ApiPoller::reconnect);

    m_serverDownTimer.setSingleShot(true);
    m_serverDownTimer.setInterval(2000);
    connect(&m_serverDownTimer, &QTimer::timeout, this, &ApiPoller::reportServerDown);

    connect(&m_ws, &QWebSocket::connected,           this, &ApiPoller::onWsConnected);
    connect(&m_ws, &QWebSocket::disconnected,        this, &ApiPoller::onWsDisconnected);
    connect(&m_ws, &QWebSocket::textMessageReceived, this, &ApiPoller::onWsMessage);

    reconnect();
}

// --- WebSocket connection ---

void ApiPoller::reconnect()
{
    m_ws.open(QUrl(Settings::wsUrl()));
}

void ApiPoller::onWsConnected()
{
    m_reconnectTimer.stop();
    m_serverDownTimer.stop();
    m_everConnected = true;

    if (!m_serverUp) {
        m_serverUp = true;
        emit serverReachabilityChanged(true);
    }
}

void ApiPoller::onWsDisconnected()
{
    if (m_everConnected)
        m_serverDownTimer.start();

    m_reconnectTimer.start();
}

void ApiPoller::reportServerDown()
{
    if (m_serverUp) {
        m_serverUp = false;
        emit serverReachabilityChanged(false);
    }
}

// --- Incoming state messages ---

void ApiPoller::onWsMessage(const QString &msg)
{
    auto    root        = QJsonDocument::fromJson(msg.toUtf8()).object();
    int     newItemId   = root["last_scanned_item_id"].toInt(0);
    QString newUpdatedAt = root["updated_at"].toString();

    if (!m_initialized) {
        m_lastItemId    = newItemId;
        m_lastUpdatedAt = newUpdatedAt;
        m_initialized   = true;
        return; // silent first message — don't pop up on pre-existing state
    }

    bool scanHappened = !newUpdatedAt.isEmpty() && (newUpdatedAt != m_lastUpdatedAt);

    m_lastItemId    = newItemId;
    m_lastUpdatedAt = newUpdatedAt;

    if (scanHappened && newItemId != 0)
        fetchViewerData();
}

// --- Fetch full viewer data on item change ---

void ApiPoller::fetchViewerData()
{
    if (m_fetchPending) return;

    m_fetchPending = true;

    auto *reply = m_nam.get(QNetworkRequest(QUrl(Settings::serverUrl() + "/viewer.get")));

    connect(reply, &QNetworkReply::finished, this, [this, reply]() {
        m_fetchPending = false;
        reply->deleteLater();

        if (reply->error() != QNetworkReply::NoError) return;

        auto root    = QJsonDocument::fromJson(reply->readAll()).object();
        bool hasItem = !root["item"].isNull() && root["item"].isObject();

        ViewerItem item;
        if (hasItem) {
            auto o      = root["item"].toObject();
            item.id         = o["id"].toInt();
            item.name       = o["name"].toString();
            item.partNumber = o["part_number"].toString();
            item.quantity   = o["quantity"].toInt();
            for (auto e : o["location_path"].toArray())
                item.locationPath << e.toObject()["name"].toString();
        }

        bool hasRef = !root["reference"].isNull() && root["reference"].isObject();

        ViewerRef ref;
        if (hasRef) {
            auto o     = root["reference"].toObject();
            ref.id       = o["id"].toInt();
            ref.name     = o["name"].toString();
            ref.fileType = o["file_type"].toString();
            ref.url      = o["url"].toString();
        }

        emit stateChanged(hasItem, item, hasRef, ref);
    });
}

// --- Outbound scan ---

void ApiPoller::postScan(const QString &qrSerial)
{
    QNetworkRequest req(QUrl(Settings::serverUrl() + "/scan.process"));
    req.setHeader(QNetworkRequest::ContentTypeHeader, "application/json");
    QJsonObject body;
    body["qr_serial"] = qrSerial;
    auto *reply = m_nam.post(req, QJsonDocument(body).toJson());
    connect(reply, &QNetworkReply::finished, this, [reply]() {
        reply->deleteLater();
    });
}
