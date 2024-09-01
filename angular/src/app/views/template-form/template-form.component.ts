import { Component, OnInit } from '@angular/core';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { emailRegEx } from '../../shared/constants/regex';

@Component({
  selector: 'oz-finance-template-form',
  templateUrl: './template-form.component.html',
  styleUrls: ['./template-form.component.scss'],
})
export class TemplateFormComponent implements OnInit {
  form: FormGroup;
  selectOptions = [
    { key: 'first', value: '1' },
    { key: 'second', value: '2' },
  ];

  constructor(private fb: FormBuilder) {}

  ngOnInit(): void {
    this.initForm();
  }

  initForm(): void {
    this.form = this.fb.group({
      text_field: new FormControl('', Validators.required),
      email: new FormControl('', [
        Validators.required,
        Validators.pattern(emailRegEx),
      ]),
      password: new FormControl('', Validators.required),
      textarea: new FormControl('', [
        Validators.required,
        Validators.minLength(5),
        Validators.maxLength(30),
      ]),
      checkbox: new FormControl(false),
      radio_button: new FormControl('first_option'),
      select: new FormControl('', Validators.required),
      ng_select: new FormControl('', Validators.required),
      toggle: new FormControl(false),
      currency_value: new FormControl(''),
      currency_type: new FormControl(''),
    });
  }

  submitForm(): void {
    return;
  }

  toggle(): void {
    const newToggleValue = !this.form.controls.toggle.value;
    this.form.patchValue({ toggle: newToggleValue });
  }

  async uploadFile(files: File[]): Promise<void> {
    const [file] = files;
    await this.readFile(file);
    // for (const file of files) {
    //   await this.readFile(file);
    // }
    return;
  }

  readFile(
    file: File
  ): Promise<
    { filename: string; file: string | ArrayBuffer | null } | boolean
  > {
    return new Promise(resolve => {
      const reader = new FileReader();
      reader.onload = (): void => {
        this.checkFileRestrictions(file).then(
          approved => {
            if (approved) {
              resolve({ filename: file.name, file: reader.result });
            } else {
              resolve(false);
            }
          },
          () => {
            resolve(false);
          }
        );
      };

      try {
        reader.readAsDataURL(file);
      } catch (exception) {
        resolve(false);
      }
    });
  }

  checkFileRestrictions(file: File): Promise<boolean> {
    const maxSize = 10 * 1024 * 1024;
    const allowedFileTypes = [
      'image/jpg',
      'image/jpeg',
      'image/png',
      'image/gif',
      'text/csv',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    return new Promise(resolve => {
      if (file.size > maxSize) {
        resolve(false);
      }

      if (!allowedFileTypes.includes(file.type)) {
        resolve(false);
      }
      resolve(true);
    });
  }

  triggerInputChange(input: HTMLInputElement): void {
    input.click();
  }
}
