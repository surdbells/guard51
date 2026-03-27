import { Component } from '@angular/core';

@Component({
  selector: 'g51-login',
  template: `
    <FlexboxLayout class="page" justifyContent="center" alignItems="center">
      <StackLayout class="form" width="80%">
        <Image src="~/assets/logo.png" class="logo mb-6" height="80"></Image>
        <Label text="Guard51" class="text-2xl text-center font-bold mb-1"></Label>
        <Label text="Security Guard App" class="text-sm text-center text-muted mb-8"></Label>
        <TextField hint="Email" keyboardType="email" autocorrect="false" class="input mb-3"></TextField>
        <TextField hint="Password" secure="true" class="input mb-4"></TextField>
        <Button text="Sign In" class="btn-primary-lg mb-3"></Button>
        <Button text="Use Fingerprint" class="btn-secondary text-center"></Button>
        <Label text="v1.0.0 • DOSTHQ Limited" class="text-xs text-center text-muted mt-8"></Label>
      </StackLayout>
    </FlexboxLayout>
  `,
})
export class LoginComponent {}
