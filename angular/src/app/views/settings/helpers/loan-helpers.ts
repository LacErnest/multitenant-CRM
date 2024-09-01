import moment from 'moment';

const DAYS_QUANTITY_TO_BLOCK_DELETION = 3;

export class LoanHelpers {
  private static checkDaysFromCreation(created_at: string): boolean {
    const curDate = moment();
    const createdDate = moment(created_at);
    const daysPassedFromCreation = curDate.diff(createdDate, 'days');

    return daysPassedFromCreation < DAYS_QUANTITY_TO_BLOCK_DELETION;
  }

  public static isLoanEditAllowed(
    paidAtDate: string,
    deletedAtDate: string
  ): boolean {
    return !paidAtDate && !deletedAtDate;
  }

  public static isLoanDeletionAllowed(
    paidAtDate: string,
    createdAtDate: string,
    deletedAtDate: string
  ): boolean {
    return (
      !paidAtDate && this.checkDaysFromCreation(createdAtDate) && !deletedAtDate
    );
  }
}
