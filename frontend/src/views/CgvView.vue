<template>
  <div class="legal-page">
    <div class="container">
      <h1 class="page-title">Conditions Générales de Vente</h1>

      <section class="legal-section">
        <h2>Article 1 – Objet</h2>
        <p>
          Les présentes Conditions Générales de Vente (CGV) régissent les ventes de produits d'impression 3D réalisées
          par Al3DPrint via son site internet. Toute commande implique l'acceptation sans réserve des présentes CGV.
        </p>
      </section>

      <section class="legal-section">
        <h2>Article 2 – Produits</h2>
        <p>
          Les produits proposés sont des objets imprimés en 3D, standards ou personnalisés. Les caractéristiques essentielles
          des produits sont présentées sur le site. Les photographies et visuels sont donnés à titre indicatif.
        </p>
      </section>

      <section class="legal-section">
        <h2>Article 3 – Prix</h2>
        <p>
          Les prix sont indiqués en euros (€) toutes taxes comprises (TTC) pour les particuliers,
          et hors taxes (HT) pour les professionnels soumis à TVA (taux de 17 %).
        </p>
        <p>
          Al3DPrint se réserve le droit de modifier ses prix à tout moment. Les produits seront facturés sur la base des
          tarifs en vigueur au moment de la validation de la commande.
        </p>
        <p>Les frais de livraison sont offerts.</p>
      </section>

      <section class="legal-section">
        <h2>Article 4 – Commande</h2>
        <p>
          La commande est validée après confirmation du paiement. Un numéro de commande est attribué et communiqué
          à l'acheteur. Al3DPrint se réserve le droit d'annuler toute commande en cas de litige avec le client,
          de suspicion de fraude ou de rupture de stock.
        </p>
      </section>

      <section class="legal-section">
        <h2>Article 5 – Paiement</h2>
        <p>
          Le paiement s'effectue en ligne par carte bancaire via la plateforme sécurisée Stripe.
          Les données bancaires ne sont pas stockées sur nos serveurs.
        </p>
        <p>La commande ne sera traitée qu'après réception du paiement complet.</p>
      </section>

      <section class="legal-section">
        <h2>Article 6 – Livraison</h2>
        <p>
          Les délais de fabrication et de livraison sont donnés à titre indicatif. Al3DPrint s'engage à expédier les
          commandes dans les meilleurs délais après confirmation du paiement.
        </p>
        <p>La livraison est gratuite pour toute commande passée sur le site.</p>
      </section>

      <section class="legal-section">
        <h2>Article 7 – Droit de rétractation</h2>
        <p>
          Conformément à l'article L221-18 du Code de la consommation, vous disposez d'un délai de 14 jours à compter
          de la réception de votre commande pour exercer votre droit de rétractation, sans avoir à justifier de motifs.
        </p>
        <p>
          <strong>Exception :</strong> Les produits personnalisés (fabriqués sur mesure à la demande du client) sont
          expressément exclus du droit de rétractation conformément à l'article L221-28 du Code de la consommation.
        </p>
      </section>

      <section class="legal-section">
        <h2>Article 8 – Garanties</h2>
        <p>
          Tous les produits bénéficient de la garantie légale de conformité (articles L217-4 et suivants du Code de la
          consommation) et de la garantie contre les vices cachés (articles 1641 et suivants du Code civil).
        </p>
      </section>

      <section class="legal-section">
        <h2>Article 9 – Responsabilité</h2>
        <p>
          Al3DPrint ne saurait être tenu responsable de l'inexécution du contrat conclu en cas de rupture de stock,
          de force majeure, de perturbation ou grève totale ou partielle des services postaux.
        </p>
      </section>

      <section class="legal-section">
        <h2>Article 10 – Litiges</h2>
        <p>
          En cas de litige, une solution amiable sera recherchée avant toute action judiciaire. À défaut, les tribunaux
          français seront compétents. Le droit français est applicable aux présentes CGV.
        </p>
        <p>
          Vous pouvez également recourir à la médiation de la consommation via la plateforme européenne de règlement
          en ligne des litiges : https://ec.europa.eu/consumers/odr
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
  background: rgba(20, 20, 20, 0.6);
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
