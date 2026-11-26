FROM node:22-bookworm-slim

WORKDIR /app
ENV PUPPETEER_SKIP_DOWNLOAD=true

COPY package.json pnpm-lock.yaml ./
RUN npm install --global pnpm@10.32.1 && pnpm install --frozen-lockfile

COPY . .
CMD ["pnpm", "dev", "--host", "0.0.0.0", "--strictPort"]
