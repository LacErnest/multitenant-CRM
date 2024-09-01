import { DiscountOption } from 'src/app/shared/interfaces/discount-option';

export const enum DiscountOptionEnum {
  DISCOUNT_5_PERCENT = 5,
  DISCOUNT_10_PERCENT = 10,
  DISCOUNT_15_PERCENT = 15,
}

export function getDiscountOptions(): DiscountOption[] {
  return [
    { key: DiscountOptionEnum.DISCOUNT_5_PERCENT, value: '-5%' },
    { key: DiscountOptionEnum.DISCOUNT_10_PERCENT, value: '-10%' },
    { key: DiscountOptionEnum.DISCOUNT_15_PERCENT, value: '-15%' },
  ];
}
