<template>
  <div class="legal-page">
    <div class="container">
      <h1 class="page-title">Politique de Confidentialité</h1>

      <section class="legal-section">
        <h2>Responsable du traitement</h2>
        <p>Al3DPrint est responsable du traitement de vos données personnelles collectées via ce site.</p>
        <p><strong>Contact :</strong> contact@al3dprint.com</p>
      </section>

      <section class="legal-section">
        <h2>Données collectées</h2>
        <p>Nous collectons les données suivantes lors de votre utilisation du site :</p>
        <ul>
          <li>Nom et prénom</li>
          <li>Adresse email</li>
          <li>Numéro de téléphone</li>
          <li>Adresse(s) de livraison</li>
          <li>Historique de commandes</li>
        </ul>
        <p>Les données sensibles (adresses, téléphone) sont chiffrées en base de données avec AES-256-CBC.</p>
      </section>

      <section class="legal-section">
        <h2>Finalités du traitement</h2>
        <p>Vos données sont traitées pour :</p>
        <ul>
          <li>Gérer votre compte client et vos commandes</li>
          <li>Traiter et livrer vos achats</li>
          <li>Vous informer du suivi de vos commandes</li>
          <li>Améliorer nos services</li>
        </ul>
      </section>

      <section class="legal-section">
        <h2>Base légale</h2>
        <p>
          Le traitement de vos données est fondé sur l'exécution du contrat de vente (article 6.1.b du RGPD) et,
          le cas échéant, sur votre consentement (article 6.1.a du RGPD) pour les communications marketing.
        </p>
      </section>

      <section class="legal-section">
        <h2>Durée de conservation</h2>
        <p>
          Vos données sont conservées pendant la durée de votre relation contractuelle avec Al3DPrint, puis archivées
          pendant 3 ans à compter de la dernière interaction, conformément aux obligations légales.
        </p>
      </section>

      <section class="legal-section">
        <h2>Vos droits (RGPD)</h2>
        <p>Conformément au RGPD, vous disposez des droits suivants :</p>
        <ul>
          <li><strong>Droit d'accès</strong> : obtenir une copie de vos données</li>
          <li><strong>Droit de rectification</strong> : corriger des données inexactes</li>
          <li><strong>Droit à l'effacement</strong> : demander la suppression de vos données</li>
          <li><strong>Droit d'opposition</strong> : vous opposer à un traitement</li>
          <li><strong>Droit à la portabilité</strong> : recevoir vos données dans un format structuré</li>
        </ul>
        <p>
          Pour exercer ces droits, contactez-nous à : <strong>contact@al3dprint.com</strong>.
          Vous pouvez également introduire une réclamation auprès de la CNIL (www.cnil.fr).
        </p>
      </section>

      <section class="legal-section">
        <h2>Cookies</h2>
        <p>
          Ce site utilise uniquement des données de session stockées localement (localStorage) pour maintenir votre
          connexion et votre panier. Aucun cookie de traçage publicitaire n'est utilisé.
        </p>
      </section>

      <p class="last-updated">Dernière mise à jour : janvier 2026</p>
    </div>
  </div>
</template>

<script setup>
</script>

<style scoped>
.legal-page {
  min-height: 100vh;
  padding: 60px 0;
}

.page-title {
  font-size: 2.5rem;
  font-weight: bold;
  color: var(--text-primary);
  margin-bottom: 40px;
  background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.legal-section {
  background: rgba(26, 26, 46, 0.6);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 30px;
  margin-bottom: 24px;
}

.legal-section h2 {
  font-size: 1.3rem;
  font-weight: 600;
  color: var(--primary-color);
  margin-bottom: 16px;
}

.legal-section p,
.legal-section li {
  color: var(--text-secondary);
  line-height: 1.7;
  margin-bottom: 10px;
}

.legal-section ul {
  padding-left: 20px;
  margin-bottom: 10px;
}

.legal-section p:last-child {
  margin-bottom: 0;
}

.last-updated {
  color: var(--text-secondary);
  font-size: 0.9rem;
  margin-top: 20px;
  font-style: italic;
}
</style>
