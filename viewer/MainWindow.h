#pragma once
#include <QWidget>
#include <QSystemTrayIcon>
#include "ApiPoller.h"
#include "ScannerInput.h"

class DocViewer;
class ItemPanel;
class ErrorOverlay;
class BrowserWindow;

class MainWindow : public QWidget
{
    Q_OBJECT
public:
    explicit MainWindow();

protected:
    void keyPressEvent(QKeyEvent *e) override;
    void resizeEvent(QResizeEvent *e) override;

private slots:
    void onStateChanged(bool hasItem, ViewerItem item, bool hasRef, ViewerRef ref);
    void onServerReachabilityChanged(bool isUp);
    void onTrayActivated(QSystemTrayIcon::ActivationReason reason);
    void toggleMute();
    void showSettings();

private:
    void setupTray();
    void wakeDisplay();
    void updateTrayIcon();

    ApiPoller     m_poller;
    ScannerInput  m_scanner;

    DocViewer          *m_docViewer;
    ItemPanel          *m_itemPanel;
    ErrorOverlay       *m_errorOverlay;
    BrowserWindow      *m_browser = nullptr;
    QSystemTrayIcon    *m_tray;
    QAction            *m_muteAction;

    int  m_lastShownItemId = 0;
    bool m_muted           = false;
    bool m_serverUp        = true;
};
