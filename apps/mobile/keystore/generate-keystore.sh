#!/bin/bash
# Generate Android signing keystore
# Run once, then keep the .jks file safe
keytool -genkey -v \
  -keystore guard51.jks \
  -keyalg RSA \
  -keysize 2048 \
  -validity 10000 \
  -alias guard51 \
  -storepass Guard51@2026 \
  -keypass Guard51@2026 \
  -dname "CN=Guard51, OU=DOSTHQ, O=DOSTHQ Limited, L=Lagos, ST=Lagos, C=NG"

echo "✅ Keystore generated: guard51.jks"
