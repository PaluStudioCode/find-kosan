/**
 * API Key authentication middleware.
 * Expects header: X-API-Key: <key>
 */
function authMiddleware(req, res, next) {
    const apiKey = req.headers['x-api-key'];
    const expectedKey = process.env.API_KEY;

    if (!expectedKey) {
        console.warn('[Auth] API_KEY not set in .env, allowing all requests.');
        return next();
    }

    if (!apiKey || apiKey !== expectedKey) {
        return res.status(401).json({
            success: false,
            error: 'Unauthorized: Invalid or missing API key',
        });
    }

    next();
}

module.exports = authMiddleware;
