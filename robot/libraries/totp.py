import pyotp
import base64

def get_totp(secret: str) -> str:
    totp = pyotp.TOTP(base64.b32encode(secret.encode()).decode())

    return totp.now()
