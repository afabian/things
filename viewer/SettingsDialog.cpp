#include "SettingsDialog.h"
#include "Settings.h"
#include <QLineEdit>
#include <QComboBox>
#include <QLabel>
#include <QPushButton>
#include <QFormLayout>
#include <QVBoxLayout>
#include <QHBoxLayout>
#include <QDir>

SettingsDialog::SettingsDialog(QWidget *parent) : QDialog(parent)
{
    setWindowTitle("Settings");
    setStyleSheet("background: #1e1e1e; color: #ddd;");
    setMinimumWidth(420);

    auto *lay  = new QVBoxLayout(this);
    auto *form = new QFormLayout();
    lay->addLayout(form);

    auto fieldStyle = "background: #111; color: #ddd; border: 1px solid #333; padding: 4px;";

    m_serverUrl = new QLineEdit(Settings::serverUrl(), this);
    m_serverUrl->setStyleSheet(fieldStyle);
    form->addRow("Server URL:", m_serverUrl);

    m_scannerDevice = new QComboBox(this);
    m_scannerDevice->setEditable(true);
    m_scannerDevice->setStyleSheet(fieldStyle);
    refreshDevices();
    int idx = m_scannerDevice->findText(Settings::scannerDevice());
    if (idx >= 0) m_scannerDevice->setCurrentIndex(idx);
    else          m_scannerDevice->setEditText(Settings::scannerDevice());
    form->addRow("Scanner device:", m_scannerDevice);

    auto *note = new QLabel(
        "Devices listed from /dev/input/by-id/. "
        "User must be in the 'input' group for evdev access.", this);
    note->setStyleSheet("font-size: 11px; color: #666;");
    note->setWordWrap(true);
    lay->addWidget(note);

    lay->addStretch();

    auto *btnRow = new QHBoxLayout();
    lay->addLayout(btnRow);
    btnRow->addStretch();
    auto *close = new QPushButton("Close", this);
    close->setStyleSheet(
        "background: #252525; border: 1px solid #3a3a3a; color: #ccc; padding: 6px 20px;");
    btnRow->addWidget(close);
    connect(close, &QPushButton::clicked, this, &QDialog::accept);

    // Settings save immediately on change
    connect(m_serverUrl, &QLineEdit::editingFinished, this, [this]() {
        Settings::setServerUrl(m_serverUrl->text().trimmed());
    });
    connect(m_scannerDevice, &QComboBox::currentTextChanged, this, [](const QString &text) {
        Settings::setScannerDevice(text);
    });
}

void SettingsDialog::refreshDevices()
{
    m_scannerDevice->addItem(""); // no device
    QDir dir("/dev/input/by-id");
    for (const auto &entry : dir.entryList(QDir::Files | QDir::System))
        m_scannerDevice->addItem("/dev/input/by-id/" + entry);
}
