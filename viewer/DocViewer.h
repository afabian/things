#pragma once
#include <QStackedWidget>
#include <QNetworkAccessManager>
#include <QPixmap>
#include <QTemporaryFile>

class QLabel;
class QTextBrowser;
class QPdfView;
class QPdfDocument;

class DocViewer : public QStackedWidget
{
    Q_OBJECT
public:
    explicit DocViewer(QWidget *parent = nullptr);
    ~DocViewer();

    void loadUrl(const QString &url, const QString &fileType);
    void showEmpty(const QString &message = {});

protected:
    void resizeEvent(QResizeEvent *e) override;

private:
    void showImage(const QByteArray &data);
    void showMarkdown(const QByteArray &data);
    void showPdf(const QByteArray &data);

    QLabel        *m_emptyLabel;
    QLabel        *m_imageLabel;
    QTextBrowser  *m_textBrowser;
    QPdfView      *m_pdfView;
    QPdfDocument  *m_pdfDoc;

    QNetworkAccessManager m_nam;
    QPixmap        m_originalPixmap;
    QTemporaryFile *m_tempPdf = nullptr;
};
