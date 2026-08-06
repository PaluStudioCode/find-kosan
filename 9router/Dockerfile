FROM node:20-alpine

WORKDIR /app

# Install 9Router globally
RUN npm install -g 9router

# Environment variables untuk headless mode (bypass interactive menu)
# 9Router akan auto-start web server di port 20128
ENV PORT=20128
ENV HOSTNAME=0.0.0.0
ENV NODE_ENV=production

# Expose port 20128 (9Router dashboard + OpenAI-compatible API)
EXPOSE 20128

# Run 9router
# Saat dijalankan non-interactively (container tanpa TTY), 9Router auto-start web server
CMD ["9router"]
