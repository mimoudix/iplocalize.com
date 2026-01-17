# IPLocalize

This project is a Symfony-based application for IP localization, dockerized for easy deployment.

## ⚙️ Configuration

After cloning the project or before building the containers, configure your environment variables.

1.  **Create `.env` file:**

    ```bash
    cp .env.dist .env
    ```

2.  **Configure `.env`:**

    | Variable              | Description                                                                                    | Example                          |
    | :-------------------- | :--------------------------------------------------------------------------------------------- | :------------------------------- |
    | `APP_ENV`             | Application environment (`dev` or `prod`).                                                     | `dev`                            |
    | `IP_LOOKUP_ENDPOINT`  | Endpoint for IP lookups. If using Docker, this might point to a local service or external API. | `http://localhost/api/v1/lookup` |
    | `LOCK_DSN`            | Helper for lock management.                                                                    | `flock`                          |
    | `MAXMIND_LICENSE_KEY` | Your MaxMind license key for GeoIP updates.                                                    | `YOUR_KEY_HERE`                  |

---

## 🤝 Contributing & Installation

To set up the project for development, ensure you have **Docker**, **Node.js**, and **NPM** installed.

### 1. Installation

Clone the repository and install dependencies:

```bash
git clone https://github.com/mimoudix/iplocalize.com.git
cd iplocalize
```

**Start the Application:**
Start the backend services (Database, App) using Docker:

```bash
docker-compose up -d
```

**Install Frontend Dependencies:**

```bash
npm install
```

### 2. Asset Management (Webpack Encore)

This project uses Symfony Webpack Encore. Use the following commands to manage assets:

- **Compile for development:**

  ```bash
  npm run dev
  ```

- **Compile and watch for changes:**

  ```bash
  npm run watch
  ```

- **Compile for production:**
  ```bash
  npm run build
  ```

---

## 🚀 Hosting on your own

Follow these steps to deploy the application on your own server.

### 1. System Setup

Ensure your system is updated and has **Docker** and **Docker Compose** installed.

```bash
sudo apt update
sudo apt install -y docker.io docker-compose
```

_(Optional) Add your user to the docker group:_

```bash
sudo usermod -aG docker $USER
newgrp docker
```

### 2. Start the Application

Build and start the containers in detached mode:

```bash
docker-compose up -d --build
```

### 3. SSL Configuration (HTTPS)

We use Certbot to generate and manage free SSL certificates.

**Install Certbot:**

```bash
sudo apt install -y certbot
```

**Generate Certificate:**
Ensure port 443 is open, then run:

```bash
sudo certbot certonly --standalone -d iplocalize.com --email yourmail@gmail.com --agree-tos
```

**Automatic Renewal:**
Add a cron job (`crontab -e`) to renew certificates automatically (e.g., every 3 days):

```cron
0 3 */3 * * cd /path/to/iplocalize && docker-compose down ; certbot renew --quiet ; docker-compose up -d
```

### 4. Maintenance

**Update GeoIP Database:**
Add a weekly cron job to keep the IP database fresh:

```cron
0 2 * * 1 docker exec iplocalize_app php bin/console app:geoip:update >> /dev/null 2>&1
```
