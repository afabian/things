#include "MainWindow.h"
#include "DocViewer.h"
#include "ItemPanel.h"
#include "ErrorOverlay.h"
#include "SettingsDialog.h"
#include "Settings.h"
#include <QSplitter>
#include <QHBoxLayout>
#include <QKeyEvent>
#include <QResizeEvent>
#include <QMenu>
#include <QIcon>
#include <QPixmap>
#include <QPainter>
#include <QPen>
#include "BrowserWindow.h"
#include <QDBusInterface>
#include <QCoreApplication>

MainWindow::MainWindow() : QWidget(nullptr)
{
    setWindowTitle("Things Viewer");
    setWindowIcon(QIcon(":/cardboard-box.svg"));
    setStyleSheet("background: #111;");

    auto *splitter = new QSplitter(Qt::Horizontal, this);
    splitter->setHandleWidth(2);
    splitter->setStyleSheet("QSplitter::handle { background: #2a2a2a; }");

    m_docViewer = new DocViewer(splitter);
    m_itemPanel = new ItemPanel(splitter);
    splitter->addWidget(m_docViewer);
    splitter->addWidget(m_itemPanel);
    splitter->setStretchFactor(0, 7);
    splitter->setStretchFactor(1, 3);

    auto *lay = new QHBoxLayout(this);
    lay->setContentsMargins(0, 0, 0, 0);
    lay->addWidget(splitter);

    m_errorOverlay = new ErrorOverlay(this);

    setupTray();

    connect(&m_poller,  &ApiPoller::stateChanged,              this,      &MainWindow::onStateChanged);
    connect(&m_poller,  &ApiPoller::serverReachabilityChanged,  this,      &MainWindow::onServerReachabilityChanged);
    connect(&m_scanner, &ScannerInput::barcodeScanned,          &m_poller, &ApiPoller::postScan);

    QString dev = Settings::scannerDevice();
    if (!dev.isEmpty()) m_scanner.open(dev);

    m_poller.start();
}

void MainWindow::setupTray()
{
    m_tray = new QSystemTrayIcon(this);
    updateTrayIcon();

    auto *menu = new QMenu();
    menu->setStyleSheet(
        "QMenu { background: #1e1e1e; color: #ddd; border: 1px solid #333; }"
        "QMenu::item:selected { background: #2a3a4a; }");

    auto *showAct = menu->addAction("Show Viewer");
    connect(showAct, &QAction::triggered, this, [this]() {
        showFullScreen();
        raise();
        activateWindow();
    });

    auto *webAct = menu->addAction("Open Management Website");
    connect(webAct, &QAction::triggered, this, [this]() {
        if (!m_browser)
            m_browser = new BrowserWindow(this);
        m_browser->openUrl(Settings::serverUrl());
    });

    menu->addSeparator();

    m_muteAction = menu->addAction("Mute");
    connect(m_muteAction, &QAction::triggered, this, &MainWindow::toggleMute);

    menu->addSeparator();

    auto *settingsAct = menu->addAction("Settings...");
    connect(settingsAct, &QAction::triggered, this, &MainWindow::showSettings);

    menu->addSeparator();

    auto *quitAct = menu->addAction("Quit");
    connect(quitAct, &QAction::triggered, this, []() { QCoreApplication::quit(); });

    m_tray->setContextMenu(menu);
    connect(m_tray, &QSystemTrayIcon::activated, this, &MainWindow::onTrayActivated);
    m_tray->show();
}

void MainWindow::updateTrayIcon()
{
    QIcon base(":/cardboard-box.svg");
    QPixmap pm = m_muted
        ? base.pixmap(22, 22, QIcon::Disabled)
        : base.pixmap(22, 22);

    if (!m_serverUp) {
        QPainter p(&pm);
        p.setRenderHint(QPainter::Antialiasing);
        p.setPen(QPen(QColor("#ff2222"), 3.0, Qt::SolidLine, Qt::RoundCap));
        int mg = 4;
        p.drawLine(mg, mg, 21 - mg, 21 - mg);
        p.drawLine(21 - mg, mg, mg, 21 - mg);
    }

    m_tray->setIcon(QIcon(pm));

    QString tip = "Things Viewer";
    if (!m_serverUp) tip += " — server unreachable";
    if (m_muted)     tip += " (muted)";
    m_tray->setToolTip(tip);
}

void MainWindow::toggleMute()
{
    m_muted = !m_muted;
    m_muteAction->setText(m_muted ? "Unmute" : "Mute");
    updateTrayIcon();
    if (m_muted && isVisible())
        hide();
}

void MainWindow::onStateChanged(bool hasItem, ViewerItem item, bool hasRef, ViewerRef ref)
{
    if (!hasItem) return;

    bool isNewItem = (item.id != m_lastShownItemId);
    m_lastShownItemId = item.id;

    m_itemPanel->update(item, hasRef, ref);

    if (hasRef)
        m_docViewer->loadUrl(ref.url, ref.fileType);
    else
        m_docViewer->showEmpty("No reference document");

    if (m_muted) return;

    if (!isVisible()) {
        wakeDisplay();
        showFullScreen();
        if (!m_serverUp)
            m_errorOverlay->showMessage("Cannot contact Server");
    } else if (isNewItem) {
        wakeDisplay();
    }
}

void MainWindow::onServerReachabilityChanged(bool isUp)
{
    m_serverUp = isUp;
    updateTrayIcon();
    if (!isVisible()) return;
    if (isUp)
        m_errorOverlay->hideMessage();
    else
        m_errorOverlay->showMessage("Cannot contact Server");
}

void MainWindow::wakeDisplay()
{
    QDBusInterface ss(
        "org.gnome.ScreenSaver",
        "/org/gnome/ScreenSaver",
        "org.gnome.ScreenSaver");
    if (ss.isValid()) ss.call("SetActive", false);
}

void MainWindow::keyPressEvent(QKeyEvent *e)
{
    if (e->key() == Qt::Key_Escape)
        hide();
    else
        QWidget::keyPressEvent(e);
}

void MainWindow::resizeEvent(QResizeEvent *e)
{
    QWidget::resizeEvent(e);
    m_errorOverlay->reposition();
}

void MainWindow::onTrayActivated(QSystemTrayIcon::ActivationReason reason)
{
    if (reason == QSystemTrayIcon::DoubleClick) {
        if (isVisible()) hide();
        else { showFullScreen(); raise(); activateWindow(); }
    }
}

void MainWindow::showSettings()
{
    SettingsDialog dlg(this);
    dlg.exec();
    m_scanner.close();
    QString dev = Settings::scannerDevice();
    if (!dev.isEmpty()) m_scanner.open(dev);
}
