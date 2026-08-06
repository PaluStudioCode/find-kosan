FROM node:20-alpine

# Install git untuk clone repo
RUN apk add --no-cache git

WORKDIR /app

# Clone 9Router dari source repository
RUN git clone https://github.com/decolua/9router.git .

# Install dependencies & build production Next.js app
# Ini bypass menu interaktif CLI & security exit yang terjadi saat pakai '9router' global command
RUN npm install && npm run build

# Environment variables untuk headless mode
ENV PORT=20128
ENV HOSTNAME=0.0.0.0
ENV NODE_ENV=production

# Expose port 20128 (9Router dashboard + OpenAI-compatible API)
EXPOSE 20128

# Run Next.js production server langsung (tanpa CLI wrapper 9router)
CMD ["npm", "run", "start"]
