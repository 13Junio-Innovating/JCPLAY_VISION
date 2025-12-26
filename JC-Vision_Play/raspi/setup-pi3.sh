#!/usr/bin/env bash
set -euo pipefail

# JC Vision Play — Setup automatizado para Raspberry Pi 3 (Chromium Kiosk)
# Este script prepara o Pi 3 para rodar o Chromium em modo kiosk com flags
# seguras (sem aceleração problemática), habilita desktop autologin e ajusta HDMI.
# Uso:
#   sudo bash setup-pi3.sh [--hostname CS-RSP-TERIVA] [--url "<PLAYER_URL>"]
# Se não informar, HOSTNAME padrão: CS-RSP-TERIVA; URL padrão: do repositório.

HOSTNAME="CS-RSP-TERIVA"
URL_DEFAULT="https://jc-vision-play.vercel.app/player/c5dc8d9fef4bdf859b1a887e566f5c89"
URL="$URL_DEFAULT"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --hostname)
      HOSTNAME="$2"; shift 2;;
    --url)
      URL="$2"; shift 2;;
    *)
      echo "Parâmetro desconhecido: $1"; exit 1;;
  esac
done

echo "==> Hostname: $HOSTNAME"
echo "==> URL: $URL"

echo "==> Instalando pacotes..."
apt update
apt install -y chromium-browser xserver-xorg x11-xserver-utils unclutter raspi-config

echo "==> Definindo hostname..."
hostnamectl set-hostname "$HOSTNAME"

echo "==> Habilitando desktop com autologin..."
if command -v raspi-config >/dev/null 2>&1; then
  # B4: Desktop Autologin
  raspi-config nonint do_boot_behaviour B4 || true
else
  echo "raspi-config não encontrado; configure boot para Desktop Autologin manualmente."
fi

echo "==> Criando perfil dedicado do Chromium..."
mkdir -p /home/pi/.kiosk-profile
chown -R pi:pi /home/pi/.kiosk-profile

echo "==> Configurando HDMI e gráficos em /boot/config.txt (backup criado)..."
cp /boot/config.txt "/boot/config.txt.bak.$(date +%Y%m%d%H%M%S)"

ensure_line() {
  local key="$1"; shift
  local value="$*"
  if ! grep -q "^${key}=" /boot/config.txt; then
    echo "${key}=${value}" >> /boot/config.txt
  else
    sed -i "s/^${key}=.*/${key}=${value}/" /boot/config.txt
  fi
}

ensure_line hdmi_force_hotplug 1
ensure_line hdmi_drive 2
ensure_line hdmi_group 2
ensure_line hdmi_mode 82
ensure_line disable_overscan 1
ensure_line gpu_mem 128

# Se overlay estiver ativo e causar problemas, comentar
if grep -q '^dtoverlay=vc4-fkms-v3d' /boot/config.txt; then
  sed -i 's/^dtoverlay=vc4-fkms-v3d/# dtoverlay=vc4-fkms-v3d/' /boot/config.txt
fi

echo "==> Desativando screen blanking e ocultando cursor no LXDE..."
AUTOSTART="/etc/xdg/lxsession/LXDE-pi/autostart"
mkdir -p "$(dirname "$AUTOSTART")"
touch "$AUTOSTART"
append_once() { grep -qxF "$1" "$AUTOSTART" || echo "$1" >> "$AUTOSTART"; }
append_once "@xset s off"
append_once "@xset -dpms"
append_once "@xset s noblank"
append_once "@unclutter -idle 0"

echo "==> Instalando serviço kiosk para Pi 3 em /etc/systemd/system/kiosk.service..."
SERVICE_PATH="/etc/systemd/system/kiosk.service"

# Se há arquivo kiosk-pi3.service no diretório atual, use-o; senão, gerar conteúdo.
if [[ -f "kiosk-pi3.service" ]]; then
  cp -f "kiosk-pi3.service" "$SERVICE_PATH"
else
  cat > "$SERVICE_PATH" <<EOF
[Unit]
Description=Chromium Kiosk (Pi 3) - JC Vision Play
After=systemd-user-sessions.service display-manager.service
Wants=graphical.target

[Service]
User=pi
Environment=DISPLAY=:0
Environment=XAUTHORITY=/home/pi/.Xauthority
Type=simple
Restart=always
RestartSec=3

ExecStart=/usr/bin/chromium-browser \
  --kiosk \
  --noerrdialogs \
  --disable-session-crashed-bubble \
  --incognito \
  --autoplay-policy=no-user-gesture-required \
  --user-data-dir=/home/pi/.kiosk-profile \
  --disable-gpu \
  --disable-features=UseOzonePlatform,VaapiVideoDecoder \
  --use-gl=swiftshader \
  --disable-accelerated-video-decode \
  --start-maximized \
  "$URL"

[Install]
WantedBy=graphical.target
EOF
fi

echo "==> Habilitando e iniciando serviço kiosk..."
systemctl daemon-reload
systemctl enable kiosk
systemctl restart kiosk || true

echo "==> Concluído. Se o Chromium abrir em preto, reinicie: sudo reboot"
echo "==> Dica: para logs do serviço: journalctl -u kiosk -b -f"