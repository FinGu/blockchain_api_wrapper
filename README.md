# blockchain_api_wrapper
php blockchain api wrapper 

```php
require 'blockchain.php';
require 'wallets.php';

$instance = new blockchain\BlockChain('http://localhost:3000');

$wallet = $instance->create_wallet('test password');

die((string)$wallet->get_balance());
```
