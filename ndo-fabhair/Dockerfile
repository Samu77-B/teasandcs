# Node API + static PWA (stripe-backend.js serves the repo root)
# Using Docker avoids Railpack "secret ID missing" build failures on some projects.
FROM node:22-alpine

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --omit=dev

COPY . .

ENV NODE_ENV=production
# Railway sets PORT; stripe-backend.js uses process.env.PORT || 3001
EXPOSE 8080

CMD ["node", "stripe-backend.js"]
