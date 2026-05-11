#include "ItemPanel.h"
#include <QLabel>
#include <QVBoxLayout>

ItemPanel::ItemPanel(QWidget *parent) : QWidget(parent)
{
    setStyleSheet("background: #1a1a1a; color: #ddd;");

    auto *lay = new QVBoxLayout(this);
    lay->setContentsMargins(20, 20, 20, 20);
    lay->setSpacing(10);

    m_name = new QLabel(this);
    m_name->setStyleSheet("font-size: 28px; font-weight: bold; color: #fff;");
    m_name->setWordWrap(true);
    lay->addWidget(m_name);

    m_partNumber = new QLabel(this);
    m_partNumber->setStyleSheet("font-size: 16px; color: #8af; font-family: monospace;");
    lay->addWidget(m_partNumber);

    lay->addSpacing(10);

    m_quantity = new QLabel(this);
    m_quantity->setStyleSheet("font-size: 22px; color: #8c8;");
    lay->addWidget(m_quantity);

    m_location = new QLabel(this);
    m_location->setStyleSheet("font-size: 14px; color: #888;");
    m_location->setWordWrap(true);
    lay->addWidget(m_location);

    lay->addStretch();

    m_refName = new QLabel(this);
    m_refName->setStyleSheet("font-size: 13px; color: #666; font-style: italic;");
    m_refName->setWordWrap(true);
    lay->addWidget(m_refName);
}

void ItemPanel::update(const ViewerItem &item, bool hasRef, const ViewerRef &ref)
{
    m_name->setText(item.name);
    m_partNumber->setText(item.partNumber.isEmpty()
        ? QString() : "[" + item.partNumber + "]");
    m_quantity->setText("Qty: " + QString::number(item.quantity));
    m_location->setText(item.locationPath.join(" > "));
    m_refName->setText(hasRef ? "Doc: " + ref.name : "No reference document");
}

void ItemPanel::clear()
{
    m_name->clear();
    m_partNumber->clear();
    m_quantity->clear();
    m_location->clear();
    m_refName->clear();
}
