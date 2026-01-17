# IPLocalize

IPLocalize is a containerized Symfony application designed for IP localization. It is fully Dockerized to ensure streamlined deployment and ease of use.

## 🤝 Contributing & Development Setup

To set up the project for local development, ensure that **Docker** is installed on your machine.

### 1. Clone the Repository

Clone the project and navigate to the directory:

```bash
git clone https://github.com/mimoudix/iplocalize.com.git
```
Navigate to the project directory:

```bash
cd iplocalize.com
```

### 2. Configuration
Initialize the environment file:

**Create `.env` file:**

  ```bash
  cp .env.dist .env
  ```

**Configure `.env`:**

Update the environment variables to match your local setup:

    | Variable              | Description                                                                                    | Example                          |
    | :-------------------- | :--------------------------------------------------------------------------------------------- | :------------------------------- |
    | `APP_ENV`             | Application environment (`dev` or `prod`).                                                     | `dev`                            |
    | `IP_LOOKUP_ENDPOINT`  | Endpoint for IP lookups. If using Docker, this might point to a local service or external API. | `http://localhost/api/v1/lookup` |
    | `LOCK_DSN`            | Helper for lock management.                                                                    | `flock`                          |
    | `MAXMIND_LICENSE_KEY` | Your MaxMind license key for GeoIP updates.                                                    | `YOUR_KEY_HERE`                  |

**Create `docker-compose.override.yml` file:**

  ```bash
  cp docker-compose.override.yml.dist docker-compose.override.yml
  ```
   Note: This override file mounts the project source into the container and sets APP_ENV=dev with APP_DEBUG=1 to facilitate debugging and faster iteration during development.

### 3. Build & Start

**Build and start the containers :**

```bash
docker-compose up -d
```

### 4. Asset Management (Webpack Encore)

This project uses Symfony Webpack Encore. Use the following commands to manage assets:

- **Compile for development:**

  ```bash
  docker exec iplocalize_app npm run dev
  ```

- **Compile and watch for changes:**

  ```bash
  docker exec iplocalize_app npm run watch
  ```

- **Compile for production:**
  ```bash
  docker exec iplocalize_app npm run build
  ```

---

## 🚀 Production Deployment (Ubuntu VPS)

Follow these steps to deploy the application on a live server.

### 1. System Prerequisites

Ensure your system is updated and has **Docker** and **Docker Compose** installed.

```bash
sudo apt update
sudo apt install -y docker.io docker-compose
```
Add your user to the docker group.

```bash
sudo usermod -aG docker ubuntu
newgrp docker
```

### 2. Install Git

```bash
sudo apt update
sudo apt-get install git
```

### 3. Clone the Repository

Clone the project and navigate to the directory:

```bash
git clone https://github.com/mimoudix/iplocalize.com.git
```
Navigate to the project directory:

```bash
cd iplocalize.com
```

### 3. Configuration 
Initialize the environment file:

**Create `.env` file:**

  ```bash
  cp .env.dist .env
  ```

**Configure `.env`:**

Update the environment variables for production:

    | Variable              | Description                                                                                    | Example                          |
    | :-------------------- | :--------------------------------------------------------------------------------------------- | :------------------------------- |
    | `APP_ENV`             | Application environment (`dev` or `prod`).                                                     | `dev`                            |
    | `IP_LOOKUP_ENDPOINT`  | Endpoint for IP lookups. If using Docker, this might point to a local service or external API. | `http://localhost/api/v1/lookup` |
    | `LOCK_DSN`            | Helper for lock management.                                                                    | `flock`                          |
    | `MAXMIND_LICENSE_KEY` | Your MaxMind license key for GeoIP updates.                                                    | `YOUR_KEY_HERE`                  |

### 4. Build & Launch

**Build and start the containers in detached mode: :**

  ```bash
  docker-compose up -d --build
  ```
### 4. Maintenance

**Update GeoIP Database:**

It is recommended to add a weekly cron job to keep the IP database up to date:

```cron
0 2 * * 1 docker exec iplocalize_app php bin/console app:geoip:update >> /dev/null 2>&1
```
