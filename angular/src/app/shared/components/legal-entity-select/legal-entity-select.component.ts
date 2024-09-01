import {
  Component,
  Input,
  OnInit,
  Output,
  EventEmitter,
  OnDestroy,
} from '@angular/core';
import { GlobalService } from 'src/app/core/services/global.service';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { CompanyLegalEntity } from 'src/app/shared/interfaces/legal-entity';
import { FormGroup } from '@angular/forms';
import { Subject } from 'rxjs';
import { skip, takeUntil } from 'rxjs/operators';
import { LegalEntityChosen } from 'src/app/shared/interfaces/legal-entity-chosen';

export type BindingMode = 'model' | 'formControl';

@Component({
  selector: 'oz-finance-legal-entity-select',
  templateUrl: './legal-entity-select.component.html',
  styleUrls: ['./legal-entity-select.component.scss'],
})
export class LegalEntitySelectComponent implements OnInit, OnDestroy {
  @Input() public legalEntityFormControlName: string;
  @Input() public legalEntityParentFormGroup: FormGroup;
  @Input() public selectedLegalEntity: string;
  @Input() public defaultValue: string;
  @Input() public canUpdateLegalEntity = false;
  @Input() public noCreatedEntity = true;
  @Input() public isPurchaseOrder = false;
  @Input() public isLegacyCustomer = false;
  @Input() public onlyDefaultEntity: boolean;

  @Output() public selectedEntityChanged =
    new EventEmitter<LegalEntityChosen>();

  public legalEntities: CompanyLegalEntity[];

  private onDestroy = new Subject<void>();

  public constructor(private globalService: GlobalService) {}

  public ngOnInit(): void {
    this.legalEntities = this.globalService.currentLegalEntities;

    if (!this.selectedLegalEntity && this.isPurchaseOrder) {
      this.checkIfDisabledControl();
    } else if (
      !this.selectedLegalEntity &&
      this.noCreatedEntity &&
      !this.isPurchaseOrder
    ) {
      this.setDefaultValue();
      this.checkIfDisabledControl();
    } else if (!this.selectedLegalEntity) {
      this.initLegalEntitiesSubscription();
      this.setDefaultValue();
      this.checkIfDisabledControl();
    }
  }

  public ngOnDestroy(): void {
    this.onDestroy?.next();
    this.onDestroy?.complete();
  }

  public setDefaultValue(): void {
    const legal: CompanyLegalEntity =
      this.isLegacyCustomer && !this.isPurchaseOrder
        ? this.legalEntities.find(e => e.local)
        : this.legalEntities.find(
            e =>
              (!this.defaultValue && e.default) ||
              (this.defaultValue && e.name === this.defaultValue)
          );

    if (!legal) {
      return;
    }

    const { country, legal_entity_id } = legal;
    this.selectedEntityChanged.emit({
      country,
      id: legal_entity_id,
      changed: false,
    });
    this.legalEntityParentFormGroup
      .get('legal_entity_id')
      .patchValue(legal_entity_id);
  }

  public legalEntityChosen(id: string): void {
    const country = this.legalEntities.find(
      e => e.legal_entity_id === id
    )?.country;
    this.selectedEntityChanged.emit({ country, id, changed: true });
  }

  private checkIfDisabledControl(): void {
    const { getUserRole, currentLegalEntities } = this.globalService;
    const userRole = getUserRole();
    const { length: entitiesLength } = currentLegalEntities || [];
    const onlyDefaultEntity =
      this.onlyDefaultEntity ??
      (userRole === UserRole.SALES_PERSON ||
        userRole === UserRole.PROJECT_MANAGER ||
        userRole === UserRole.PROJECT_MANAGER_RESTRICTED);

    if (entitiesLength < 2) {
      this.legalEntityParentFormGroup.get('legal_entity_id')?.disable();
      if (this.isPurchaseOrder && !onlyDefaultEntity) this.setDefaultValue();
    }
  }

  private initLegalEntitiesSubscription(): void {
    this.globalService
      .getCurrentLegalEntitiesObservable()
      .pipe(skip(1), takeUntil(this.onDestroy))
      .subscribe(entities => {
        this.legalEntities = entities;
      });
  }
}
