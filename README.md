# 🌍 IPLocalize

IPLocalize is a containerized **Symfony** application designed for IP localization. It is fully **Dockerized** to ensure streamlined deployment and ease of use.

## 🤝 Contributing & Development Setup

To set up the project for local development, ensure that **Docker** is installed on your machine.

### 1. 📂 Clone the Repository

Clone the project and navigate to the directory:

```bash
git clone https://github.com/mimoudix/iplocalize.com.git
```

Navigate to the project directory:

```bash
cd iplocalize.com
```

### 2. ⚙️ Configuration

Initialize the environment file:

Create `.env` file:

```bash
cp .env.dist .env
```

Configure `.env`:

Update the environment variables to match your local setup.

| Variable | Description | Example |
| :--- | :--- | :--- |
| `APP_ENV` | Application environment (`dev` or `prod`). | `dev` |
| `IP_LOOKUP_ENDPOINT` | Endpoint for IP lookups. If using Docker, this might point to a local service or external API. | `http://localhost/api/v1/lookup` |
| `LOCK_DSN` | Helper for lock management. | `flock` |
| `MAXMIND_LICENSE_KEY` | Your MaxMind license key for GeoIP updates. | `YOUR_KEY_HERE` |

Create `docker-compose.override.yml` file:

```bash
cp docker-compose.override.yml.dist docker-compose.override.yml
```

> [!NOTE]
> This override file mounts the project source into the container and sets `APP_ENV=dev` with `APP_DEBUG=1` to facilitate debugging and faster iteration during development.

### 3. 🏗️ Build & Start

Build and start the containers:

```bash
docker-compose up -d
```

### 4. 📦 Asset Management (Webpack Encore)

This project uses **Symfony Webpack Encore**. Use the following commands to manage assets:

- Compile for development:

  ```bash
  docker exec iplocalize_app npm run dev
  ```

- Compile and watch for changes:

  ```bash
  docker exec iplocalize_app npm run watch
  ```

- Compile for production:

  ```bash
  docker exec iplocalize_app npm run build
  ```

---

## 🚀 Production Deployment (Ubuntu VPS)

Follow these steps to deploy the application on a live server.

### Prerequisites:

- A server running **Ubuntu**.
- A valid **domain name** (e.g., `iplocalize.com`) pointing to your server's IP address.
- Port **443** must be open and accessible (check your firewall).

### 1. 🖥️ System Setup

Ensure your system is updated and has **Docker** and **Docker Compose** installed.

```bash
sudo apt update
sudo apt install -y docker.io docker-compose
```

Add your user to the docker group.

```bash
sudo usermod -aG docker $USER
newgrp docker
```

### 2. 🔧 Install Git & Certbot

```bash
sudo apt update
sudo apt install -y git certbot
```

### 3. 🔒 Generate SSL Certificates (Before Build)

Generate your certificates now while Port 80 is free.

> [!IMPORTANT]
> Replace `iplocalize.com` with your actual domain in the commands below.

> [!NOTE]
> When Certbot finishes, it will output a path where the keys are saved, usually: `/etc/letsencrypt/live/yourdomain.com/`. Copy this domain name/path, as you will need to paste it in Step 6.

```bash
sudo certbot certonly --standalone -d yourdomain.com -d www.yourdomain.com
```

_(This creates the certificates in `/etc/letsencrypt`, which Docker will mount automatically in the next steps.)_

### 4. 📥 Clone & Configure

Clone the repository:

```bash
git clone https://github.com/mimoudix/iplocalize.com.git
cd iplocalize.com
```

Setup Environment: 

```bash
cp .env.dist .env
```

Configure your `.env` variables (ensure `APP_ENV=prod`), you can use a text editor like **vim** or **nano** to edit this file (e.g., `vim .env`).

| Variable | Description | Example |
| :--- | :--- | :--- |
| `APP_ENV` | Application environment (`dev` or `prod`). | `prod` |
| `IP_LOOKUP_ENDPOINT` | Endpoint for IP lookups. If using Docker, this might point to a local service or external API. | `http://localhost/api/v1/lookup` |
| `LOCK_DSN` | Helper for lock management. | `flock` |
| `MAXMIND_LICENSE_KEY` | Your MaxMind license key for GeoIP updates. | `YOUR_KEY_HERE` |

### 5. 🐳 Build & Start

Now that certificates exist, build and start the containers.

```bash
docker-compose up -d --build
```

### 6. 🌐 Finalize SSL Configuration

Run these commands one by one to configure **Apache** inside the container. This enables SSL, points it to your certificates, and sets the correct public folder.

> [!WARNING]
> Critical Step: In the commands below, replace `yourdomain.com` with the specific folder name you noted in Step 3.

> [!TIP]
> Missed the path? Retrieve it anytime using this command:
> ```bash
> sudo certbot certificates
> ```

Enable SSL Module:

```bash
docker exec iplocalize_app a2enmod ssl
```

Update Certificate Paths: (Replace `yourdomain.com` with your actual domain folder)

```bash
docker exec iplocalize_app sed -i 's|/etc/ssl/certs/ssl-cert-snakeoil.pem|/etc/letsencrypt/live/yourdomain.com/fullchain.pem|g' /etc/apache2/sites-available/default-ssl.conf
```
```bash
docker exec iplocalize_app sed -i 's|/etc/ssl/private/ssl-cert-snakeoil.key|/etc/letsencrypt/live/yourdomain.com/privkey.pem|g' /etc/apache2/sites-available/default-ssl.conf
```

Set Correct Public Directory:

```bash
docker exec iplocalize_app sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/default-ssl.conf
```

Enable Site & Reload:

```bash
docker exec iplocalize_app a2ensite default-ssl
docker exec iplocalize_app service apache2 reload
```

Configure SSL Auto-Renewal (One-Time Setup)

Certbot renews certificates automatically, but it needs permission to stop Docker briefly during the process to free up Port 80. Run this command one time to save this setting:

_(Replace `yourdomain.com` with your actual domain)_

```bash
sudo certbot certonly --standalone --force-renewal -d yourdomain.com -d www.yourdomain.com --pre-hook "docker stop iplocalize_app" --post-hook "docker start iplocalize_app"
```

### 7. 🛠️ Maintenance

Update GeoIP Database:

It is recommended to add a weekly cron job to keep the IP database up to date.

1.  Install Cron (if not already installed):
    ```bash
    sudo apt update
    sudo apt install -y cron
    ```

2.  Open Crontab:
    ```bash
    crontab -e
    ```

3. Add the Cron Job:
    Paste the following line at the bottom of the file:

    ```cron
    0 2 * * 1 docker exec iplocalize_app php bin/console app:geoip:update >> /dev/null 2>&1
    ```
