import { UserRole } from 'src/app/shared/enums/user-role.enum';

export const settingsRoutesRoles = {
  templates: [
    UserRole.ADMINISTRATOR,
    UserRole.OWNER,
    UserRole.ACCOUNTANT,
    UserRole.HUMAN_RESOURCES,
    UserRole.OWNER_READ_ONLY,
  ],
  users: [UserRole.ADMINISTRATOR, UserRole.OWNER, UserRole.OWNER_READ_ONLY],
  services: [
    UserRole.ADMINISTRATOR,
    UserRole.OWNER,
    UserRole.ACCOUNTANT,
    UserRole.OWNER_READ_ONLY,
  ],
  loans: [UserRole.ADMINISTRATOR],
  companyLegalEntities: [UserRole.ADMINISTRATOR, UserRole.ACCOUNTANT],
  salesCommissions: [UserRole.ADMINISTRATOR, UserRole.ACCOUNTANT],
  rentCosts: [
    UserRole.ADMINISTRATOR,
    UserRole.OWNER,
    UserRole.ACCOUNTANT,
    UserRole.OWNER_READ_ONLY,
  ],
  notificationSettings: [UserRole.ADMINISTRATOR],
  emailManagement: [
    UserRole.ADMINISTRATOR,
    UserRole.OWNER,
    UserRole.ACCOUNTANT,
    UserRole.OWNER_READ_ONLY,
  ],
};
