#include "DocViewer.h"
#include <QLabel>
#include <QTextBrowser>
#include <QPdfView>
#include <QPdfDocument>
#include <QNetworkReply>
#include <QNetworkRequest>
#include <QTemporaryFile>
#include <QResizeEvent>
#include <QUrl>

DocViewer::DocViewer(QWidget *parent) : QStackedWidget(parent)
{
    m_emptyLabel = new QLabel(this);
    m_emptyLabel->setAlignment(Qt::AlignCenter);
    m_emptyLabel->setStyleSheet("background: #111; color: #444; font-size: 22px;");
    addWidget(m_emptyLabel);

    m_imageLabel = new QLabel(this);
    m_imageLabel->setAlignment(Qt::AlignCenter);
    m_imageLabel->setStyleSheet("background: #111;");
    addWidget(m_imageLabel);

    m_textBrowser = new QTextBrowser(this);
    m_textBrowser->setStyleSheet(
        "background: #111; color: #ddd; font-size: 16px; border: none;");
    addWidget(m_textBrowser);

    m_pdfDoc = new QPdfDocument(this);
    m_pdfView = new QPdfView(this);
    m_pdfView->setDocument(m_pdfDoc);
    m_pdfView->setPageMode(QPdfView::PageMode::MultiPage);
    m_pdfView->setStyleSheet("background: #333;");
    addWidget(m_pdfView);

    showEmpty();
}

DocViewer::~DocViewer()
{
    m_pdfDoc->close();
    delete m_tempPdf;
}

void DocViewer::showEmpty(const QString &message)
{
    m_emptyLabel->setText(message.isEmpty() ? "No reference document" : message);
    setCurrentWidget(m_emptyLabel);
}

void DocViewer::loadUrl(const QString &url, const QString &fileType)
{
    showEmpty("Loading...");
    auto *reply = m_nam.get(QNetworkRequest(QUrl(url)));
    connect(reply, &QNetworkReply::finished, this, [this, reply, fileType]() {
        reply->deleteLater();
        if (reply->error() != QNetworkReply::NoError) {
            showEmpty("Load failed: " + reply->errorString());
            return;
        }
        QByteArray data = reply->readAll();
        if      (fileType == "pdf")   showPdf(data);
        else if (fileType == "image") showImage(data);
        else                          showMarkdown(data);
    });
}

void DocViewer::showImage(const QByteArray &data)
{
    m_originalPixmap = QPixmap();
    if (!m_originalPixmap.loadFromData(data)) {
        showEmpty("Invalid image");
        return;
    }
    m_imageLabel->setPixmap(
        m_originalPixmap.scaled(size(), Qt::KeepAspectRatio, Qt::SmoothTransformation));
    setCurrentWidget(m_imageLabel);
}

void DocViewer::showMarkdown(const QByteArray &data)
{
    m_textBrowser->setMarkdown(QString::fromUtf8(data));
    setCurrentWidget(m_textBrowser);
}

void DocViewer::showPdf(const QByteArray &data)
{
    m_pdfDoc->close();
    delete m_tempPdf;
    m_tempPdf = new QTemporaryFile(this);
    if (!m_tempPdf->open()) { showEmpty("Temp file error"); return; }
    m_tempPdf->write(data);
    m_tempPdf->flush();
    if (m_pdfDoc->load(m_tempPdf->fileName()) != QPdfDocument::DocumentError::NoError) {
        showEmpty("PDF load failed");
        return;
    }
    setCurrentWidget(m_pdfView);
}

void DocViewer::resizeEvent(QResizeEvent *e)
{
    QStackedWidget::resizeEvent(e);
    if (currentWidget() == m_imageLabel && !m_originalPixmap.isNull()) {
        m_imageLabel->setPixmap(
            m_originalPixmap.scaled(e->size(), Qt::KeepAspectRatio, Qt::SmoothTransformation));
    }
}
