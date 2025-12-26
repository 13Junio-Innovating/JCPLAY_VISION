# JCPLAY_VISION - Sistema de Digital Signage

Sistema completo de sinalização digital (Digital Signage) desenvolvido em PHP, focado em gerenciamento de mídias, playlists e telas para estabelecimentos comerciais.

## 🚀 Funcionalidades

### 📺 Gestão de Telas
- Cadastro e gerenciamento de múltiplas telas.
- Geração de links únicos (Player Key) para reprodução.
- Associação rápida de playlists às telas.
- Botão "Abrir Player" para visualização direta.

### 🎵 Sistema de Playlists (Drag & Drop)
- Criação intuitiva de playlists.
- **Interface Drag & Drop**: Arraste mídias para organizar a sequência.
- Definição de tempo de duração personalizado por item.
- Atualização em tempo real nos players conectados.

### 📱 Gerenciamento de Mídias
- **Upload de Arquivos**: Suporte a vídeos e imagens de alta resolução (até 500MB).
- **Links Externos**: Suporte a URLs de sites.
- **YouTube**: Integração nativa (converte links comuns para embed automaticamente).
- Pré-visualização de arquivos enviados.

### 🔄 Player Inteligente
- Reprodução contínua (Loop).
- Atualização automática de conteúdo (Polling de 30s) sem recarregar a página.
- Suporte a vídeos, imagens e iframes.
- Otimizado para Raspberry Pi e modo quiosque.

---

## 🛠️ Tecnologias

- **Backend**: PHP 7.4+ (Nativo)
- **Banco de Dados**: MySQL / MariaDB
- **Frontend**: HTML5, JavaScript (Vanilla), Tailwind CSS (CDN)
- **Servidor**: Apache / Nginx

---

## 📦 Instalação e Configuração

### Pré-requisitos
- Servidor Web (Apache/Nginx) com PHP instalado.
- Banco de Dados MySQL.

### 1. Clonar o Repositório
```bash
git clone https://github.com/13Junio-Innovating/JCPLAY_VISION.git
cd JCPLAY_VISION
```

### 2. Configuração do Banco de Dados
1. Crie um banco de dados MySQL (ex: `JC-Vision-Play`).
2. Importe o arquivo SQL localizado em `JC-Vision_Play/database.sql`.

### 3. Configuração de Ambiente (.env)
1. Navegue até a pasta do projeto: `cd JC-Vision_Play`.
2. Copie o arquivo de exemplo:
   - **Windows**: `copy .env.example .env`
   - **Linux/Mac**: `cp .env.example .env`
3. Edite o arquivo `.env` com suas credenciais do banco:
```env
DB_HOST=127.0.0.1
DB_NAME=JC-Vision-Play
DB_USER=seu_usuario
DB_PASS=sua_senha
```

### 4. Configuração do Servidor Web
Aponte o DocumentRoot do seu servidor para a pasta `JC-Vision_Play/public`.

**Exemplo com PHP Built-in Server (para testes):**
```bash
cd JC-Vision_Play
php -S localhost:8000 -t public
```
Acesse: `http://localhost:8000`

### 5. Configuração de Uploads (Opcional mas Recomendado)
O sistema já inclui um `.htaccess` otimizado, mas garanta que seu `php.ini` permita uploads grandes se necessário:
```ini
upload_max_filesize = 500M
post_max_size = 500M
```

---

## 🖥️ Configuração Raspberry Pi (Kiosk Mode)

Scripts de configuração automática estão disponíveis em `JC-Vision_Play/raspi/`.
Consulte `JC-Vision_Play/raspi/README.md` para detalhes específicos de instalação em Raspberry Pi.

---

## 📝 Uso Básico

1. **Login**: Acesse o sistema e faça login (crie uma conta se necessário).
2. **Upload**: Vá em "Mídias" e envie seus vídeos/imagens ou cadastre links.
3. **Playlist**: Crie uma playlist e arraste as mídias desejadas. Salve.
4. **Telas**: Cadastre uma tela, vincule a playlist e clique em "Abrir Player" ou copie o link para usar no dispositivo final.
