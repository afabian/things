#pragma once
#include <QObject>
#include <QString>

class QSocketNotifier;

class ScannerInput : public QObject
{
    Q_OBJECT
public:
    explicit ScannerInput(QObject *parent = nullptr);
    ~ScannerInput();

    bool open(const QString &devicePath);
    void close();
    bool isOpen() const;

signals:
    void barcodeScanned(const QString &qrSerial);

private slots:
    void readEvent();

private:
    int m_fd = -1;
    QSocketNotifier *m_notifier = nullptr;
    QString m_buffer;
    bool m_shiftDown = false;
};
