const assert = require('node:assert/strict');
const test = require('node:test');
const { requestLaravelApi } = require('../src/ai-tools');

test('calls Laravel AI endpoints with query parameters and internal API key', async (context) => {
    const originalFetch = global.fetch;
    context.after(() => {
        global.fetch = originalFetch;
    });

    global.fetch = async (url, options) => {
        const requestUrl = new URL(url);
        assert.equal(requestUrl.pathname, '/api/ai/search-kos');
        assert.equal(requestUrl.searchParams.get('city'), 'KOTA PALU');
        assert.equal(options.method, 'GET');
        assert.equal(options.headers.Accept, 'application/json');

        return {
            ok: true,
            json: async () => ({ count: 1, results: [] }),
        };
    };

    const result = await requestLaravelApi('/search-kos', {
        params: { city: 'KOTA PALU' },
    });

    assert.deepEqual(result, { count: 1, results: [] });
});

test('reports the Laravel endpoint when an internal API request fails', async (context) => {
    const originalFetch = global.fetch;
    const originalError = console.error;
    context.after(() => {
        global.fetch = originalFetch;
        console.error = originalError;
    });

    global.fetch = async () => {
        const error = new TypeError('fetch failed');
        error.cause = new Error('connect ECONNREFUSED');
        throw error;
    };
    console.error = () => {};

    await assert.rejects(
        requestLaravelApi('/search-kos'),
        /Laravel API tidak dapat dihubungi: connect ECONNREFUSED/
    );
});
