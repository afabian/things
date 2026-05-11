#include "ErrorOverlay.h"
#include <QLabel>
#include <QHBoxLayout>

ErrorOverlay::ErrorOverlay(QWidget *parent) : QWidget(parent)
{
    setAttribute(Qt::WA_TransparentForMouseEvents);
    setStyleSheet(
        "ErrorOverlay {"
        "  background-color: #2a0000;"
        "  border: 2px solid #cc2222;"
        "  border-radius: 4px;"
        "}"
    );

    auto *lay = new QHBoxLayout(this);
    lay->setContentsMargins(20, 12, 20, 12);

    m_label = new QLabel(this);
    m_label->setStyleSheet(
        "color: #ff5555;"
        "font-size: 20px;"
        "font-weight: bold;"
        "background: transparent;"
        "border: none;"
    );
    m_label->setAlignment(Qt::AlignCenter);
    lay->addWidget(m_label);

    hide();
}

void ErrorOverlay::showMessage(const QString &message)
{
    m_label->setText(message);
    adjustSize();
    reposition();
    show();
    raise();
}

void ErrorOverlay::hideMessage()
{
    hide();
}

void ErrorOverlay::reposition()
{
    if (!parentWidget()) return;
    QSize ps = parentWidget()->size();
    QSize s  = sizeHint();
    setGeometry((ps.width() - s.width()) / 2, 24, s.width(), s.height());
}
