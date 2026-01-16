# IPLocalize

This project is a Symfony-based application for IP localization, dockerized for easy deployment.

## 🚀 Deployment (Hosting)

Follow these steps to set up the application on a fresh Ubuntu server.

### 1. System Setup & Docker Installation

Update your system and install Docker and Docker Compose.

```bash
sudo apt update
sudo apt install -y docker.io docker-compose
```

### 2. Configure Docker Permissions

Add the current user to the docker group so you don't have to use `sudo` for every docker command.

```bash
sudo usermod -aG docker ubuntu
newgrp docker
```

Verify the installation:

```bash
docker --version
docker-compose --version
```

### 3. Start the Application

Build and start the containers in detached mode.

```bash
docker-compose up -d --build
```

---

## 🔒 SSL Configuration (HTTPS)

We use Certbot to generate and manage free SSL certificates via Let's Encrypt. The certificates are mounted into the Docker container.

### 1. Install Certbot

```bash
sudo apt install -y certbot
certbot --version
```

### 2. Generate Certificate

Run the standalone generator. Ensure port 443 needs is open on your firewall.

```bash
sudo certbot certonly --standalone -d iplocalize.com --email mohcinemimoudi@gmail.com --agree-tos
```

### 3. Automatic Renewal

The certificates expire every 90 days. Set up a cron job to automatically renew them and reload the application.

**Test renewal:**
```bash
sudo certbot renew --dry-run
```

**Add to Crontab:**
Open your crontab:
```bash
crontab -e
```

Add the following line to renew the certificate and restart the container every 3 days at 3 AM:

```cron
0 3 */3 * * cd /home/ubuntu/iplocalize && /usr/bin/docker-compose down ; /usr/bin/certbot renew --quiet ; /usr/bin/docker-compose up -d
```

---

## 🛠 Manual Maintenance

### GeoIP Database Updates

To keep the IP database fresh, add this cron job to update it weekly (e.g., Mondays at 2 AM).

```cron
0 2 * * 1 docker exec iplocalize_app php bin/console app:geoip:update >> /dev/null 2>&1
```
*(Note: Ensure this runs inside the container or php is available on host)*

---

## 💻 Development (Asset Management)

This project uses **Symfony Webpack Encore** for asset management.

### Prerequisites
*   Node.js & NPM installed.

### Commands

**1. Install Dependencies**
```bash
npm install
```

**2. Development Builds**
Compile assets for development:
```bash
npm run dev
```

Compile and watch for changes (hot reload):
```bash
npm run watch
```

Start the development server:
```bash
npm run dev-server
```

**3. Production Build**
Minify and optimize assets for production:
```bash
npm run build
```
