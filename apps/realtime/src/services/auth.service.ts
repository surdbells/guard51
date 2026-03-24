import jwt from "jsonwebtoken";

export interface TokenPayload {
  sub: string;
  tenant_id: string;
  role: string;
  iat: number;
  exp: number;
}

export class AuthService {
  constructor(private readonly secret: string) {}

  verifyToken(token: string): TokenPayload | null {
    try {
      return jwt.verify(token, this.secret) as TokenPayload;
    } catch {
      return null;
    }
  }
}
