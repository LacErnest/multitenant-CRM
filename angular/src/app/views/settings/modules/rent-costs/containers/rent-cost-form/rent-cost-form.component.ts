import { transition, trigger, useAnimation } from '@angular/animations';
import { Component, OnInit } from '@angular/core';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import moment from 'moment';
import { ToastrService } from 'ngx-toastr';
import { finalize } from 'rxjs/operators';
import {
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from 'src/app/shared/animations/browser-animations';
import { dateBeforeValidator } from 'src/app/shared/validators/date-before.validator';
import { integerValidator } from 'src/app/shared/validators/integer-validator';
import { RentCost } from 'src/app/views/settings/modules/rent-costs/interfaces/rent-cost';
import { RentCostsService } from 'src/app/views/settings/modules/rent-costs/rent-costs.service';
import { GlobalService } from '../../../../../../core/services/global.service';
import { UserRole } from '../../../../../../shared/enums/user-role.enum';
import { environment } from '../../../../../../../environments/environment';
import { getCurrencySymbol } from '@angular/common';
import { EnumService } from '../../../../../../core/services/enum.service';

@Component({
  selector: 'oz-finance-rent-cost-form',
  templateUrl: './rent-cost-form.component.html',
  styleUrls: ['./rent-cost-form.component.scss'],
  animations: [
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class RentCostFormComponent implements OnInit {
  public currencyPrefix: string;
  public isLoading = false;
  public rentCost: RentCost;
  public rentCostForm: FormGroup;

  public constructor(
    private fb: FormBuilder,
    private rentCostsService: RentCostsService,
    private route: ActivatedRoute,
    private router: Router,
    private toastrService: ToastrService,
    private globalService: GlobalService,
    private enumService: EnumService
  ) {}

  public get canSubmit(): boolean {
    return this.rentCostForm.valid && this.rentCostForm.dirty;
  }

  public ngOnInit(): void {
    this.getResolvedData();
    this.setCurrencyPrefix();
    this.initRentCostForm();
    this.patchValueRentCostForm();
  }

  public submitForm(): void {
    const rentCost = this.rentCostForm.getRawValue();
    rentCost.start_date = moment(rentCost.start_date).format('YYYY-MM-DD');

    if (rentCost.end_date) {
      rentCost.end_date = moment(rentCost.end_date).format('YYYY-MM-DD');
    }

    if (this.rentCost) {
      this.editRentCost(rentCost);
    } else {
      this.createRentCost(rentCost);
    }
  }

  private isOwnerReadOnly(): boolean {
    return this.globalService.getUserRole() === UserRole.OWNER_READ_ONLY;
  }

  private getResolvedData(): void {
    const data = this.route.snapshot.data;
    this.rentCost = data.rentCost;
  }

  private initRentCostForm(): void {
    this.rentCostForm = this.fb.group(
      {
        amount: new FormControl(undefined, [
          Validators.required,
          Validators.min(1),
          Validators.max(1000000),
          integerValidator,
        ]),
        name: new FormControl(undefined, Validators.maxLength(50)),
        start_date: new FormControl(undefined, [Validators.required]),
        end_date: new FormControl(undefined),
      },
      { validators: [dateBeforeValidator('start_date', 'end_date')] }
    );

    if (this.isOwnerReadOnly()) {
      this.rentCostForm.disable();
    }
  }

  private patchValueRentCostForm(): void {
    if (this.rentCost) {
      this.rentCostForm.patchValue(this.rentCost);
    }
  }

  private createRentCost(rentCost: RentCost): void {
    this.isLoading = true;

    this.rentCostsService
      .createRentCost(rentCost)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(response => {
        this.toastrService.success('Rent cost created successfully', 'Success');
        this.router
          .navigate([
            `/${this.globalService.currentCompany.id}/settings/rent_costs`,
          ])
          .then();
      });
  }

  private editRentCost(rentCost: RentCost): void {
    this.isLoading = true;

    this.rentCostsService
      .editRentCost(rentCost, this.rentCost.id)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(response => {
        this.rentCost = response;
        this.patchValueRentCostForm();
        this.toastrService.success('Rent cost updated successfully', 'Success');
        this.rentCostForm.markAsPristine();
        this.router
          .navigate([
            `/${this.globalService.currentCompany.id}/settings/rent_costs`,
          ])
          .then();
      });
  }

  private setCurrencyPrefix(): void {
    const currencyCode =
      this.globalService.getUserRole() === UserRole.ADMINISTRATOR
        ? environment.currency
        : this.globalService.userCurrency;

    this.currencyPrefix =
      getCurrencySymbol(
        this.enumService.getEnumMap('currencycode').get(currencyCode),
        'wide'
      ) + ' ';
  }
}
