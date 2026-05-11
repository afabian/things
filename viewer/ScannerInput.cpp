#include "ScannerInput.h"
#include <QSocketNotifier>
#include <fcntl.h>
#include <unistd.h>
#include <sys/ioctl.h>
#include <linux/input.h>

struct KeyEntry { int code; char n; char s; };
static const KeyEntry KEY_MAP[] = {
    {KEY_A,'a','A'},{KEY_B,'b','B'},{KEY_C,'c','C'},{KEY_D,'d','D'},
    {KEY_E,'e','E'},{KEY_F,'f','F'},{KEY_G,'g','G'},{KEY_H,'h','H'},
    {KEY_I,'i','I'},{KEY_J,'j','J'},{KEY_K,'k','K'},{KEY_L,'l','L'},
    {KEY_M,'m','M'},{KEY_N,'n','N'},{KEY_O,'o','O'},{KEY_P,'p','P'},
    {KEY_Q,'q','Q'},{KEY_R,'r','R'},{KEY_S,'s','S'},{KEY_T,'t','T'},
    {KEY_U,'u','U'},{KEY_V,'v','V'},{KEY_W,'w','W'},{KEY_X,'x','X'},
    {KEY_Y,'y','Y'},{KEY_Z,'z','Z'},
    {KEY_0,'0',')'},{KEY_1,'1','!'},{KEY_2,'2','@'},{KEY_3,'3','#'},
    {KEY_4,'4','$'},{KEY_5,'5','%'},{KEY_6,'6','^'},{KEY_7,'7','&'},
    {KEY_8,'8','*'},{KEY_9,'9','('},
    {KEY_MINUS,'-','_'},{KEY_EQUAL,'=','+'},{KEY_DOT,'.','>'},{KEY_COMMA,',','<'},
    {0,0,0}
};

ScannerInput::ScannerInput(QObject *parent) : QObject(parent) {}

ScannerInput::~ScannerInput() { close(); }

bool ScannerInput::open(const QString &devicePath)
{
    close();
    if (devicePath.isEmpty()) return false;
    m_fd = ::open(devicePath.toLocal8Bit().constData(), O_RDONLY | O_NONBLOCK);
    if (m_fd < 0) return false;
    ioctl(m_fd, EVIOCGRAB, 1); // exclusive grab — scanner won't type into other apps
    m_notifier = new QSocketNotifier(m_fd, QSocketNotifier::Read, this);
    connect(m_notifier, &QSocketNotifier::activated, this, &ScannerInput::readEvent);
    return true;
}

void ScannerInput::close()
{
    delete m_notifier;
    m_notifier = nullptr;
    if (m_fd >= 0) {
        ioctl(m_fd, EVIOCGRAB, 0);
        ::close(m_fd);
        m_fd = -1;
    }
    m_buffer.clear();
    m_shiftDown = false;
}

bool ScannerInput::isOpen() const { return m_fd >= 0; }

void ScannerInput::readEvent()
{
    struct input_event ev;
    while (read(m_fd, &ev, sizeof(ev)) == (ssize_t)sizeof(ev)) {
        if (ev.type != EV_KEY) continue;
        if (ev.code == KEY_LEFTSHIFT || ev.code == KEY_RIGHTSHIFT) {
            m_shiftDown = (ev.value != 0); // 1=down, 0=up, 2=repeat
            continue;
        }
        if (ev.value != 1) continue; // key-down events only
        if (ev.code == KEY_ENTER || ev.code == KEY_KPENTER) {
            if (!m_buffer.isEmpty()) {
                emit barcodeScanned(m_buffer);
                m_buffer.clear();
            }
            continue;
        }
        for (int i = 0; KEY_MAP[i].code != 0; ++i) {
            if (KEY_MAP[i].code == ev.code) {
                m_buffer += (m_shiftDown ? KEY_MAP[i].s : KEY_MAP[i].n);
                break;
            }
        }
    }
}
