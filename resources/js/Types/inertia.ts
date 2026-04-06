import type { AuthUser } from './auth';
import type { AppInfo, Flash } from './shared';

export interface SharedProps {
    auth: {
        user: AuthUser | null;
    };
    flash: Flash;
    app: AppInfo;
}
