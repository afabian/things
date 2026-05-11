#include "BrowserWindow.h"
#include <QWebEngineView>
#include <QCloseEvent>
#include <QSettings>
#include <QIcon>

BrowserWindow::BrowserWindow(QWidget *parent) : QMainWindow(parent)
{
    setWindowTitle("Things — Inventory");
    setWindowIcon(QIcon(":/cardboard-box.svg"));

    m_view = new QWebEngineView(this);
    m_view->page()->setBackgroundColor(QColor("#181818")); // dark while loading
    setCentralWidget(m_view);

    statusBar()->hide();

    if (!restoreGeometry(QSettings().value("browser/geometry").toByteArray()))
        resize(1280, 900);
}

void BrowserWindow::openUrl(const QString &url)
{
    if (m_view->url().isEmpty() || m_view->url() == QUrl("about:blank"))
        m_view->load(QUrl(url));

    show();
    raise();
    activateWindow();
}

void BrowserWindow::closeEvent(QCloseEvent *e)
{
    QSettings().setValue("browser/geometry", saveGeometry());
    hide();
    e->ignore(); // keep alive — tray action reopens it
}
