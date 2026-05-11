#pragma once
#include <QMainWindow>

class QWebEngineView;

class BrowserWindow : public QMainWindow
{
    Q_OBJECT
public:
    explicit BrowserWindow(QWidget *parent = nullptr);

    void openUrl(const QString &url);

protected:
    void closeEvent(QCloseEvent *e) override;

private:
    QWebEngineView *m_view;
};
