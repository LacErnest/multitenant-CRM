export enum ContactGender {
  MALE = 'male',
  FEMALE = 'female',
}

export function getContactGenderDescriptions(): {
  key: string;
  value: string;
}[] {
  return [
    { key: 'Male', value: ContactGender.MALE },
    { key: 'Female', value: ContactGender.FEMALE },
  ];
}
