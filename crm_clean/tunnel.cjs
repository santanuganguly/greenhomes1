const localtunnel = require('localtunnel');

(async () => {
  const tunnel = await localtunnel({ port: 3000 });
  console.log(tunnel.url);
  tunnel.on('close', () => {
    // tunnels are closed
  });
})();
