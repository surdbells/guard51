<?php
echo "Generated ENCRYPTION_KEY:\n";
echo base64_encode(random_bytes(32)) . "\n\n";
echo "Add to your .env file:\n";
echo "ENCRYPTION_KEY=<paste the key above>\n";
