<?php

return [

    /*
    |--------------------------------------------------------------------------
    | NAS SSH destination
    |--------------------------------------------------------------------------
    | NAS_HOST         - IP or hostname (e.g. 192.168.1.50 or nas.ateneya.com via Cloudflare Tunnel)
    | NAS_USER         - SSH user on the NAS (e.g. root, admin)
    | NAS_KEY_PATH     - Private SSH key ON THIS SERVER (gestao.ateneya.com) to authenticate to NAS
    | NAS_BASE_PATH    - Absolute path on the NAS where backups are stored
    | NAS_SSH_PROXY_CMD- Optional: ProxyCommand for Cloudflare Tunnel
    |   Example: "cloudflared access ssh --hostname nas.ateneya.com"
    |
    */

    'nas' => [
        'host'      => env('NAS_HOST', ''),
        'user'      => env('NAS_USER', 'root'),
        'key_path'  => env('NAS_KEY_PATH', ''),
        'base_path' => env('NAS_BASE_PATH', '/volume1/backups'),
        'port'      => (int) env('NAS_PORT', 22),
        'proxy_cmd' => env('NAS_SSH_PROXY_CMD', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | SSH key for connecting to backup targets
    |--------------------------------------------------------------------------
    | If set, this key is used for ALL servers regardless of the per-server
    | ssh_key_path field (which stores the path on the local dev machine).
    | On gestao.ateneya.com set this to /root/.ssh/ateneya_vps_key
    |
    */

    'ssh_key' => env('BACKUP_SSH_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Local temp directory for intermediate backup files
    |--------------------------------------------------------------------------
    */

    'tmp_dir' => env('BACKUP_TMP_DIR', '/tmp/bm-backups'),

];
