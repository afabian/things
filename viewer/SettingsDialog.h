#pragma once
#include <QDialog>

class QLineEdit;
class QComboBox;

class SettingsDialog : public QDialog
{
    Q_OBJECT
public:
    explicit SettingsDialog(QWidget *parent = nullptr);

private:
    void refreshDevices();

    QLineEdit *m_serverUrl;
    QComboBox *m_scannerDevice;
};
