import { transition, trigger, useAnimation } from '@angular/animations';
import { Component, OnInit } from '@angular/core';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { Observable } from 'rxjs';
import { finalize } from 'rxjs/operators';
import {
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from 'src/app/shared/animations/browser-animations';
import { LegalEntity } from 'src/app/shared/interfaces/legal-entity';
import { LegalEntitiesService } from 'src/app/views/legal-entities/legal-entities.service';

@Component({
  selector: 'oz-finance-legal-entity-form',
  templateUrl: './legal-entity-form.component.html',
  styleUrls: ['./legal-entity-form.component.scss'],
  animations: [
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class LegalEntityFormComponent implements OnInit {
  public legalEntity: LegalEntity;
  public isLoading = false;
  public legalEntityForm: FormGroup;

  public constructor(
    private fb: FormBuilder,
    private legalEntitiesService: LegalEntitiesService,
    private route: ActivatedRoute,
    private router: Router,
    private toastrService: ToastrService
  ) {}

  public ngOnInit(): void {
    this.initLegalEntityForm();
    this.getResolvedData();
    this.patchLegalEntityForm();
  }

  public get formHeading(): string {
    return this.legalEntity
      ? 'Legal Entity: ' + this.legalEntity.id
      : 'Create legal entity';
  }

  // region form-related public methods
  public showRequiredError(formGroup: FormGroup, controlName: string): boolean {
    if (!formGroup) {
      return false;
    }

    return (
      formGroup?.controls[controlName]?.errors?.required &&
      formGroup?.controls[controlName]?.dirty
    );
  }

  public submit(): void {
    this.isLoading = true;

    const legalEntityObservable = this.createLegalEntityObservable();

    legalEntityObservable
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        res => this.processLegalEntitySuccess(res),
        () => this.processLegalEntityError()
      );
  }

  public get europeanBankFromGroup(): FormGroup {
    return this.legalEntityForm?.get('european_bank') as FormGroup;
  }

  public get americanBankFromGroup(): FormGroup {
    return this.legalEntityForm?.get('american_bank') as FormGroup;
  }

  public disableSubmitBtn(): boolean {
    return (
      this.isLoading ||
      this.legalEntityForm?.invalid ||
      !this.legalEntityForm?.dirty
    );
  }

  public toggleAmericanBank(): void {
    if (this.legalEntityForm.get('american_bank')) {
      this.legalEntityForm.removeControl('american_bank');
      this.legalEntityForm.markAsDirty();
    } else {
      this.legalEntityForm.addControl(
        'american_bank',
        LegalEntityFormComponent.createAmericanBankFormGroup()
      );
    }
  }
  // end region

  private getResolvedData(): void {
    this.legalEntity = this.route.snapshot.data.legalEntity;

    if (this.legalEntity.american_bank) {
      this.legalEntityForm.addControl(
        'american_bank',
        LegalEntityFormComponent.createAmericanBankFormGroup()
      );
    }
  }

  // region form-related private methods
  private static createAddressFormGroup(): FormGroup {
    return new FormGroup({
      addressline_1: new FormControl(undefined, [
        Validators.required,
        Validators.maxLength(128),
      ]),
      addressline_2: new FormControl(undefined, Validators.maxLength(128)),
      city: new FormControl(undefined, [
        Validators.required,
        Validators.maxLength(128),
      ]),
      region: new FormControl(undefined, Validators.maxLength(128)),
      postal_code: new FormControl(undefined, [
        Validators.required,
        Validators.maxLength(128),
      ]),
      country: new FormControl(undefined, Validators.required),
    });
  }

  private static createAmericanBankFormGroup(): FormGroup {
    return new FormGroup({
      account_number: new FormControl(undefined, Validators.required),
      bank_address: LegalEntityFormComponent.createAddressFormGroup(),
      name: new FormControl(undefined, Validators.required),
      routing_number: new FormControl(undefined, Validators.required),
      usa_account_number: new FormControl(undefined, Validators.required),
      usa_routing_number: new FormControl(undefined, Validators.required),
    });
  }

  private initLegalEntityForm(): void {
    this.legalEntityForm = this.fb.group({
      name: new FormControl(undefined, [
        Validators.required,
        Validators.maxLength(128),
      ]),
      vat_number: new FormControl(undefined, [
        Validators.required,
        Validators.maxLength(20),
      ]),
      legal_entity_address: LegalEntityFormComponent.createAddressFormGroup(),
      european_bank: new FormGroup({
        bank_address: LegalEntityFormComponent.createAddressFormGroup(),
        bic: new FormControl(undefined),
        name: new FormControl(undefined, Validators.required),
        iban: new FormControl(undefined),
      }),
      usdc_wallet_address: new FormControl(undefined),
    });
  }

  private patchLegalEntityForm(): void {
    if (this.legalEntity) {
      this.legalEntityForm.patchValue(this.legalEntity);
      this.checkIfReadonlyForm();
    }
  }

  private checkIfReadonlyForm(): void {
    if (this.legalEntity.deleted_at) {
      this.legalEntityForm.disable();
    }
  }
  // end region

  private createLegalEntityObservable(): Observable<LegalEntity> {
    const legalEntity = this.legalEntityForm.getRawValue();

    if (this.legalEntity) {
      legalEntity.id = this.legalEntity.id;
    }

    return this.legalEntity
      ? this.legalEntitiesService.updateLegalEntity(legalEntity)
      : this.legalEntitiesService.createLegalEntity(legalEntity);
  }

  private processLegalEntitySuccess(legalEntity: LegalEntity): void {
    const msg = `Legal entity was successfully ${this.legalEntity ? 'updated' : 'created'}`;
    this.toastrService.success(msg, 'Success');

    if (this.legalEntity) {
      this.legalEntity = legalEntity;
      this.patchLegalEntityForm();
      this.legalEntityForm.markAsPristine();
    } else {
      this.router.navigate([`/legal_entities/${legalEntity.id}`]);
    }
  }

  private processLegalEntityError(): void {
    const msg = `Legal entity was not ${this.legalEntity ? 'updated' : 'created'}`;
    this.toastrService.error(msg, 'Error');
  }
}
