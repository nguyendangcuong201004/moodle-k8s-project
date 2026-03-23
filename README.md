# HCMUT LMS — Moodle K8s Project

Moodle LMS containerized with Docker, deployed on Kubernetes (DigitalOcean) with automated CI/CD via GitHub Actions.

## Requirements

- Docker & Docker Compose
- Git

---

## Local Development

```bash
# 1. Clone repo
git clone https://github.com/nguyendangcuong201004/moodle-k8s-project.git
cd moodle-k8s-project

# 2. Fix src directory permissions (only needed once)
sudo chown -R $USER:$USER src/

# 3. Start
docker-compose up -d

# 4. Open browser
# http://localhost:8081
# First run will show Moodle installer (~2-3 min)
```

**Local database credentials:**

| Field    | Value      |
|----------|------------|
| Host     | db         |
| Database | moodle     |
| User     | moodleuser |
| Password | Anhmeow123 |

**Stop:**
```bash
docker-compose down
```

**Rebuild after Dockerfile changes:**
```bash
docker-compose down && docker-compose up -d --build
```

> **WSL2 note**: If you see a 403 error after starting, run `docker-compose down && docker-compose up -d` to fix the bind mount issue.

---

## Project Structure

```
moodle-k8s-project/
├── src/                          # Moodle source code
│   └── public/
│       └── theme/boost/          # Boost theme (customized)
│           ├── templates/        # Mustache templates
│           └── style/
│               └── hcmut-custom.css  # Custom UI & animations
├── moodle_data/                  # Uploaded files (gitignored)
├── pg_data/                      # PostgreSQL data (gitignored)
├── Dockerfile                    # PHP 8.2 + Apache image
├── docker-compose.yml            # Local dev environment
├── docker-entrypoint.sh          # Generates config.php from env vars
└── .github/workflows/
    └── cicd.yml                  # CI/CD pipeline
```

---

## CI/CD Pipeline

### Branching Strategy

```
feature/* → develop → main
              ↓          ↓
           staging    production
           (auto)     (manual)
```

| Branch    | Trigger           | Action                             |
|-----------|-------------------|------------------------------------|
| `develop` | push              | Build image → Auto deploy to staging |
| `main`    | merge from develop| Keeps production-ready code         |
| manual    | workflow_dispatch | Deploy to production               |

### Workflow

```bash
# 1. Work on a feature
git checkout develop
# ... make changes ...
git add .
git commit -m "feat: your change"
git push origin develop
# → GitHub Actions auto builds and deploys to staging

# 2. Test on staging: https://staging-lms.ndcuong.online

# 3. Merge to main when ready
git checkout main
git merge develop
git push origin main

# 4. Deploy to production (manual)
# GitHub → Actions → Run workflow → deploy-production
# Enter image tag (e.g. sha-a1b2c3d)
```

### Manual Production Deploy

1. Go to **GitHub → Actions → Build, Push & Deploy Moodle**
2. Click **Run workflow**
3. Action: `deploy-production`
4. Image tag: copy from the build step output (e.g. `sha-3db1774`)
5. Click **Run workflow**

---

## GitHub Secrets & Variables

### Secrets (`Settings → Secrets and variables → Actions → Secrets`)

| Secret               | Description                  |
|----------------------|------------------------------|
| `DO_TOKEN`           | DigitalOcean API token        |
| `DOCKERHUB_USERNAME` | Docker Hub username           |
| `DOCKERHUB_TOKEN`    | Docker Hub access token       |

### Variables (`Settings → Secrets and variables → Actions → Variables`)

| Variable                | Example value                          |
|-------------------------|----------------------------------------|
| `DO_STAGING_CLUSTER`    | `moodle-cluster-staging-29183`         |
| `DO_PRODUCTION_CLUSTER` | `moodle-cluster-production-69087`      |
| `MOODLE_STAGING_WWWROOT`| `https://staging-lms.ndcuong.online`   |
| `MOODLE_WWWROOT`        | `https://lms.ndcuong.online`           |

> Cluster names change every time `setup.sh` runs. Get the new name with:
> ```bash
> KUBECONFIG=~/projects/moodle-k8s-infra/digitalocean/kubeconfig-staging \
>   kubectl config current-context
> # → do-sgp1-moodle-cluster-staging-XXXXX (drop the "do-sgp1-" prefix)
> ```

---

## Docker Image

**Image:** `ndcuongdevops/moodle-lms`

| Tag         | When created                  |
|-------------|-------------------------------|
| `staging`   | Push to `develop`              |
| `latest`    | Push to `main`                 |
| `sha-xxxxxx`| Every build (short commit SHA) |
| `v1.0.0`    | Git tag `v*`                   |

---

## Infrastructure

Cluster and database infrastructure is managed separately in the `moodle-k8s-infra` repo using Terraform.

```bash
# Provision staging / production
cd moodle-k8s-infra/digitalocean
./setup.sh staging
./setup.sh production

# Teardown
./destroy.sh staging
./destroy.sh production
```
