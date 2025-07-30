# Docker Setup Guide

This project uses Docker for local development.

## Requirements

Make sure you have the following installed:

- Docker Desktop (Windows)
- WSL 2 with Ubuntu (this setup is tested using WSL Ubuntu)

> Docker Desktop should be configured to use WSL 2 as its backend and your Ubuntu distro should have access to Docker.

## Getting Started

To start the containers, run the following command from the project root:

```bash
docker compose up -d --build