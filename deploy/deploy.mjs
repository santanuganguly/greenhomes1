import fs from 'fs';
import * as ftp from 'basic-ftp';
import archiver from 'archiver';
import path from 'path';

async function deploy() {
    console.log("Zipping crm folder...");
    
    await new Promise((resolve, reject) => {
        const output = fs.createWriteStream('crm_full.zip');
        const archive = archiver('zip', { zlib: { level: 5 } });
        
        output.on('close', function() {
            console.log(archive.pointer() + ' total bytes zipped');
            resolve();
        });
        
        archive.on('error', function(err) {
            reject(err);
        });
        
        archive.pipe(output);
        
        archive.glob('**/*', {
            cwd: 'crm',
            ignore: ['node_modules/**', '.git/**', '.env.testing']
        });
        
        archive.finalize();
    });

    console.log("Connecting to FTP...");
    const client = new ftp.Client();
    client.ftp.verbose = true;
    
    try {
        await client.access({
            host: "ftp.thegreen.homes",
            user: "u495244335crm",
            password: "GreenHomesCRM@2026!",
            secure: false
        });
        
        console.log("Connected! Uploading crm.zip...");
        await client.uploadFrom('crm_full.zip', 'crm.zip');
        
        console.log("Uploading unzip.php...");
        await client.uploadFrom('unzip.php', 'unzip.php');
        
        console.log("Done uploading!");
    } catch (err) {
        console.log("FTP Error: ", err);
    }
    client.close();
}

deploy();
