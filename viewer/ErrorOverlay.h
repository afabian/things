#pragma once
#include <QWidget>

class QLabel;

class ErrorOverlay : public QWidget
{
    Q_OBJECT
public:
    explicit ErrorOverlay(QWidget *parent);
    void showMessage(const QString &message);
    void hideMessage();
    void reposition();

private:
    QLabel *m_label;
};
