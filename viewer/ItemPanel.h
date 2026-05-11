#pragma once
#include <QWidget>
#include "ApiPoller.h"

class QLabel;

class ItemPanel : public QWidget
{
    Q_OBJECT
public:
    explicit ItemPanel(QWidget *parent = nullptr);
    void update(const ViewerItem &item, bool hasRef, const ViewerRef &ref);
    void clear();

private:
    QLabel *m_name;
    QLabel *m_partNumber;
    QLabel *m_quantity;
    QLabel *m_location;
    QLabel *m_refName;
};
